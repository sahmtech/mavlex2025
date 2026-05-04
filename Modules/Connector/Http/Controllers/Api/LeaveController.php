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

            $businessId = (int) $user->business_id;

            $input = [
                'business_id' => $businessId,
                'user_id' => $user->id,
                'essentials_leave_type_id' => (int) $request->input('essentials_leave_type_id'),
                'start_date' => $start,
                'end_date' => $end,
                'reason' => $request->input('reason'),
                'status' => 'pending',
            ];
            $ref_count = $this->moduleUtil->setAndGetReferenceCount('leave', $businessId);
            $business = Business::findOrFail($businessId);
            $settings = ! empty($business->essentials_settings) ? json_decode($business->essentials_settings, true) : [];
            if (! is_array($settings)) {
                $settings = [];
            }
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
            \Log::emergency('Leave API: '.$e->getFile().':'.$e->getLine().' '.$e->getMessage(), ['exception' => $e]);

            $msg = config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong');

            return $this->otherExceptions($msg);
        }
    }

    /**
     * All leave requests for the authenticated user plus balance per leave type
     * (used vs remaining within the same month / financial year / lifetime rules as the web app).
     *
     * @queryParam status string optional Filter requests: pending, approved, cancelled.
     * @queryParam year integer optional Filter requests by start_date calendar year.
     * @queryParam as_of string optional Balance snapshot date (Y-m-d). Defaults to today.
     */
    public function myLeavesAndBalance(Request $request)
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

        $businessId = (int) $user->business_id;

        $asOf = Carbon::now();
        if ($request->filled('as_of')) {
            $validator = Validator::make($request->only('as_of'), [
                'as_of' => 'required|date',
            ]);
            if ($validator->fails()) {
                return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
            }
            $asOf = Carbon::parse($request->input('as_of'))->startOfDay();
        }

        $filterValidator = Validator::make($request->only(['status', 'year']), [
            'status' => 'nullable|in:pending,approved,cancelled',
            'year' => 'nullable|integer|min:1970|max:2100',
        ]);
        if ($filterValidator->fails()) {
            return $this->setStatusCode(422)->respondWithError($filterValidator->errors()->first());
        }

        $listQuery = EssentialsLeave::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->with(['leave_type:id,leave_type,max_leave_count,leave_count_interval'])
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $listQuery->where('status', $request->input('status'));
        }
        if ($request->filled('year')) {
            $listQuery->whereYear('start_date', (int) $request->input('year'));
        }

        $leavesForList = $listQuery->get();

        $requests = $leavesForList->map(function (EssentialsLeave $leave) {
            $days = $this->leaveDaysInclusive($leave->start_date, $leave->end_date);

            return [
                'id' => $leave->id,
                'ref_no' => $leave->ref_no,
                'status' => $leave->status,
                'essentials_leave_type_id' => $leave->essentials_leave_type_id,
                'leave_type_name' => optional($leave->leave_type)->leave_type,
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'days_count' => $days,
                'reason' => $leave->reason,
                'created_at' => $leave->created_at,
            ];
        });

        $business = Business::find($businessId);
        $fyStartMonth = (int) ($business && $business->fy_start_month ? $business->fy_start_month : 1);

        $allLeavesForBalance = EssentialsLeave::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->get(['essentials_leave_type_id', 'status', 'start_date', 'end_date']);

        $leaveTypes = EssentialsLeaveType::where('business_id', $businessId)
            ->orderBy('leave_type')
            ->get(['id', 'leave_type', 'max_leave_count', 'leave_count_interval']);

        $balanceByType = [];
        foreach ($leaveTypes as $lt) {
            $period = $this->leaveBalancePeriodBounds($lt->leave_count_interval, $asOf, $fyStartMonth);
            $daysByStatus = ['pending' => 0, 'approved' => 0, 'cancelled' => 0];

            foreach ($allLeavesForBalance as $row) {
                if ((int) $row->essentials_leave_type_id !== (int) $lt->id) {
                    continue;
                }
                if (! isset($daysByStatus[$row->status])) {
                    continue;
                }
                if (! $this->leaveStartInBalancePeriod($row->start_date, $period)) {
                    continue;
                }
                $daysByStatus[$row->status] += $this->leaveDaysInclusive($row->start_date, $row->end_date);
            }

            $maxRaw = $lt->max_leave_count;
            $maxDays = ($maxRaw !== null && $maxRaw !== '') ? (int) $maxRaw : null;
            $usedApproved = (int) $daysByStatus['approved'];
            $remaining = null;
            if ($maxDays !== null) {
                $remaining = max(0, $maxDays - $usedApproved);
            }

            $balanceByType[] = [
                'leave_type_id' => (int) $lt->id,
                'leave_type' => $lt->leave_type,
                'max_days' => $maxDays,
                'leave_count_interval' => $lt->leave_count_interval,
                'period' => $period ? [
                    'start' => $period['start']->toDateString(),
                    'end' => $period['end']->toDateString(),
                ] : null,
                'days' => $daysByStatus,
                'used_days' => $usedApproved,
                'remaining_days' => $remaining,
            ];
        }

        return $this->respond([
            'data' => [
                'balance_as_of' => $asOf->toDateString(),
                'requests' => $requests,
                'balance_by_leave_type' => $balanceByType,
            ],
        ]);
    }

    private function leaveDaysInclusive($startDate, $endDate): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        return (int) $start->diffInDays($end) + 1;
    }

    /**
     * Same interval semantics as EssentialsLeaveController::checkLeaveAvailability (month / FY / lifetime).
     *
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon}|null
     */
    private function leaveBalancePeriodBounds(?string $interval, Carbon $asOf, int $fyStartMonth): ?array
    {
        if ($interval === 'month') {
            $start = $asOf->copy()->startOfMonth()->startOfDay();
            $end = $asOf->copy()->endOfMonth()->endOfDay();

            return ['start' => $start, 'end' => $end];
        }

        if ($interval === 'year') {
            $currentYear = $asOf->year;
            $fyStart = $asOf->month >= $fyStartMonth
                ? Carbon::createFromDate($currentYear, $fyStartMonth, 1)->startOfDay()
                : Carbon::createFromDate($currentYear - 1, $fyStartMonth, 1)->startOfDay();
            $fyEnd = $fyStart->copy()->addYear()->subDay()->endOfDay();

            return ['start' => $fyStart, 'end' => $fyEnd];
        }

        return null;
    }

    private function leaveStartInBalancePeriod($leaveStartDate, ?array $period): bool
    {
        if ($period === null) {
            return true;
        }
        $sd = Carbon::parse($leaveStartDate)->startOfDay();

        return ! $sd->lt($period['start']) && ! $sd->gt($period['end']);
    }
}
