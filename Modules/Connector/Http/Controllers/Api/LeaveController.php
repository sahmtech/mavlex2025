<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Business;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsLeaveType;
use Modules\Essentials\Notifications\NewLeaveNotification;

/**
 * @group Leave management
 * @authenticated
 *
 * Leave requests for Essentials HRM (applicant is the authenticated API user).
 */
class LeaveController extends ApiController
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        parent::__construct();
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * List leave types for the authenticated user's business.
     *
     * @response scenario="success" {
     *   "data": [
     *     {"id": 1, "leave_type": "Annual", "max_leave_count": 10, "leave_count_interval": "year"}
     *   ]
     * }
     */
    public function leaveTypes(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            return $this->setStatusCode(403)->respondWithError(__('essentials::lang.leave_api_essentials_not_installed'));
        }

        $user = Auth::user();
        if (! ($user->can('superadmin') || $user->can('essentials.crud_own_leave') || $user->can('essentials.crud_all_leave'))) {
            return $this->respondUnauthorized();
        }

        if (! $user->can('superadmin') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'essentials_module')) {
            return $this->setStatusCode(403)->respondWithError(__('essentials::lang.leave_api_subscription'));
        }

        $types = EssentialsLeaveType::where('business_id', $user->business_id)
            ->orderBy('leave_type')
            ->get(['id', 'leave_type', 'max_leave_count', 'leave_count_interval']);

        return $this->respond(['data' => $types]);
    }

    /**
     * Create a leave request for the authenticated user.
     *
     * @bodyParam essentials_leave_type_id integer required Leave type id from GET leave-types.
     * @bodyParam start_date string required Start date (e.g. Y-m-d).
     * @bodyParam end_date string required End date, same or after start_date.
     * @bodyParam reason string optional Reason / note.
     *
     * @response scenario="success" {
     *   "success": true,
     *   "msg": "...",
     *   "data": {"id": 1, "ref_no": "2026/0001", "status": "pending"}
     * }
     */
    public function storeLeave(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            return $this->setStatusCode(403)->respondWithError(__('essentials::lang.leave_api_essentials_not_installed'));
        }

        $user = Auth::user();
        if (! ($user->can('superadmin') || $user->can('essentials.crud_own_leave') || $user->can('essentials.crud_all_leave'))) {
            return $this->respondUnauthorized();
        }

        if (! $user->can('superadmin') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'essentials_module')) {
            return $this->setStatusCode(403)->respondWithError(__('essentials::lang.leave_api_subscription'));
        }

        $validator = Validator::make($request->all(), [
            'essentials_leave_type_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'nullable|string|max:65000',
        ]);

        if ($validator->fails()) {
            return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
        }

        $leaveType = EssentialsLeaveType::where('business_id', $user->business_id)
            ->where('id', (int) $request->input('essentials_leave_type_id'))
            ->first();

        if (empty($leaveType)) {
            return $this->setStatusCode(422)->respondWithError(__('essentials::lang.leave_api_invalid_leave_type'));
        }

        try {
            $start = Carbon::parse($request->input('start_date'))->format('Y-m-d');
            $end = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        } catch (\Exception $e) {
            return $this->setStatusCode(422)->respondWithError(__('essentials::lang.leave_api_invalid_dates'));
        }

        if ($start > $end) {
            return $this->setStatusCode(422)->respondWithError(__('essentials::lang.leave_api_end_before_start'));
        }

        try {
            DB::beginTransaction();

            $input = [
                'business_id' => $businessId,
                'user_id' => $user->id,
                'essentials_leave_type_id' => (int) $request->input('essentials_leave_type_id'),
                'start_date' => $start,
                'end_date' => $end,
                'reason' => $request->input('reason'),
                'status' => 'pending',
            ];

            $businessId = (int) $user->business_id;
            $ref_count = $this->moduleUtil->setAndGetReferenceCount('leave', $businessId);
            $business = Business::findOrFail($businessId);
            $settings = ! empty($business->essentials_settings) ? json_decode($business->essentials_settings, true) : [];
            $prefix = ! empty($settings['leave_ref_no_prefix']) ? $settings['leave_ref_no_prefix'] : '';
            $input['ref_no'] = $this->moduleUtil->generateReferenceNumber('leave', $ref_count, $businessId, $prefix);

            $leave = EssentialsLeave::create($input);

            DB::commit();

            try {
                $leave->load('user');
                $admins = $this->moduleUtil->get_admins($businessId);
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new NewLeaveNotification($leave));
                }
            } catch (\Exception $notifyEx) {
                \Log::warning('Leave API: notification failed: '.$notifyEx->getMessage());
            }

            return $this->respond([
                'success' => true,
                'msg' => __('lang_v1.added_success'),
                'data' => [
                    'id' => $leave->id,
                    'ref_no' => $leave->ref_no,
                    'status' => $leave->status,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'essentials_leave_type_id' => $leave->essentials_leave_type_id,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('Leave API: '.$e->getFile().':'.$e->getLine().' '.$e->getMessage());

            return $this->otherExceptions(__('messages.something_went_wrong'));
        }
    }
}
