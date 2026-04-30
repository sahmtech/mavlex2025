<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Business;
use App\Category;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsUserDevice;
use Modules\Connector\Transformers\CommonResource;

/**
 * @group Attendance management
 * @authenticated
 *
 * APIs for managing attendance
 */
class AttendanceController extends ApiController
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Get Attendance
     *
     * @urlParam user_id required id of the user Example: 1
     * @response {
            "data": {
                "id": 4,
                "user_id": 1,
                "business_id": 1,
                "clock_in_time": "2020-09-12 13:13:00",
                "clock_out_time": "2020-09-12 13:15:00",
                "essentials_shift_id": 3,
                "ip_address": null,
                "clock_in_note": "test clock in from api",
                "clock_out_note": "test clock out from api",
                "created_at": "2020-09-12 13:14:39",
                "updated_at": "2020-09-12 13:15:39"
            }
        }
     */
    public function getAttendance($user_id)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $business_id = $user->business_id;

        $attendance = \Modules\Essentials\Entities\EssentialsAttendance::where('business_id', $business_id)
                                    ->where('user_id', $user_id)
                                    ->orderBy('clock_in_time', 'desc')
                                    ->first();

        return new CommonResource($attendance);
    }

    /**
     * Monthly attendance calendar (presence / absence summary + daily strips).
     *
     * @queryParam year integer required Example: 2025
     * @queryParam month integer required 1–12 Example: 10
     * @queryParam user_id integer Optional; defaults to authenticated user. Requires `essentials.crud_all_attendance` for others.
     *
     * @response scenario="success" {
     *   "data": {
     *     "attended": 0,
     *     "late": 0,
     *     "absent": 22,
     *     "out": 0,
     *     "vacation": 0,
     *     "weekend": 9,
     *     "no_clockout": 0,
     *     "total_late_minutes": 0,
     *     "total_overtime_minutes": 0,
     *     "month_name": "October",
     *     "days_before": [],
     *     "days": [],
     *     "days_after": []
     *   }
     * }
     */
    public function getAttendanceByDate(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $year = $request->query('year');
        $month = $request->query('month');

        if ($year === null || $year === '' || $month === null || $month === '') {
            return $this->setStatusCode(422)->respondWithError('Year and month are required.');
        }

        $monthNum = (int) $month;
        if ($monthNum < 1 || $monthNum > 12) {
            return $this->setStatusCode(422)->respondWithError('Month must be between 1 and 12.');
        }

        $authUser = Auth::user();
        $business_id = $authUser->business_id;

        $can_view_own = $authUser->can('essentials.view_own_attendance');
        $can_view_all = $authUser->can('essentials.crud_all_attendance');
        $can_api_own_attendance = $authUser->can('essentials.allow_users_for_attendance_from_api');
        $is_admin = $this->moduleUtil->is_admin($authUser, $business_id);

        if (! ($authUser->can('superadmin') || $can_view_own || $can_view_all || $is_admin || $can_api_own_attendance)) {
            return $this->respondUnauthorized();
        }

        $target_user_id = (int) ($request->query('user_id') ?: $authUser->id);

        if ($target_user_id !== (int) $authUser->id && ! ($can_view_all || $authUser->can('superadmin'))) {
            return $this->respondUnauthorized();
        }

        if (! User::where('business_id', $business_id)->where('id', $target_user_id)->exists()) {
            return $this->setStatusCode(404)->respondWithError('User not found for this business.');
        }

        $business = Business::findOrFail($business_id);
        $settings = ! empty($business->essentials_settings) ? json_decode($business->essentials_settings, true) : [];

        $permitted_locations = $authUser->permitted_locations($business_id);

        $essentialsUtil = new \Modules\Essentials\Utils\EssentialsUtil;
        $data = $essentialsUtil->getAttendanceCalendarByMonth(
            $business_id,
            $target_user_id,
            (int) $year,
            $monthNum,
            $settings,
            $permitted_locations
        );

        return new CommonResource($data);
    }

    /**
     * Clock In
     *
     * [User must have "essentials.allow_users_for_attendance_from_api" permission to Clock in]
     *
     * @bodyParam user_id integer required id of the user Example: 1
     * @bodyParam clock_in_time string Clock in time.If not given current date time will be used Fromat: Y-m-d H:i:s Example:2000-06-13 13:13:00
     * @bodyParam clock_in_note string Clock in note.
     * @bodyParam ip_address string IP address.
     * @bodyParam latitude string Latitude of the clock in location.
     * @bodyParam longitude string Longitude of the clock in location.
     * @bodyParam location_id integer optional Branch id for geofence; defaults to first location.
     * @bodyParam clockin_image file optional Image file (e.g. selfie / proof).
     *
     * @response {
         "success":true,
         "msg":"Clocked In successfully",
         "type":"clock_in"
     }
     */
    public function clockin(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $user = Auth::user();
            $business_id = $user->business_id;
            $business = Business::findOrFail($business_id);
            $settings = $business->essentials_settings;
            $settings = ! empty($settings) ? json_decode($settings, true) : [];
            $essentialsUtil = new \Modules\Essentials\Utils\EssentialsUtil;

            $geo = $essentialsUtil->validateApiClockInGeofence(
                $business_id,
                $user,
                $request->input('latitude'),
                $request->input('longitude'),
                $request->input('clock_in_note'),
                $request->input('location_id')
            );
            if ($geo !== null) {
                return response()->json([
                    'error' => [
                        'message' => $geo['message'],
                        'code' => $geo['code'],
                    ],
                ], (int) ($geo['status'] ?? 400));
            }

            $clockInGeofenceStatus = $essentialsUtil->getClockInGeofenceStatus(
                $business_id,
                $user,
                $request->input('latitude'),
                $request->input('longitude'),
                $request->input('clock_in_note'),
                $request->input('location_id')
            );

            $data = [
                'business_id' => $business_id,
                // Match web attendance: clock-in for authenticated user unless explicitly overridden.
                'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : $user->id,
                'clock_in_time' => empty($request->input('clock_in_time')) ? \Carbon::now() : $request->input('clock_in_time'),
                'clock_in_note' => $request->input('clock_in_note'),
                'ip_address' => $request->input('ip_address'),
            ];

            if ($request->hasFile('clockin_image')) {
                $path = $request->file('clockin_image')->store('clock_in_images', 'public');
                if (! empty($path)) {
                    $data['clock_in_image'] = $path;
                }
            }

            $data['clock_in_geofence_status'] = $clockInGeofenceStatus;

            if (! empty($settings['is_location_required'])) {
                $long = $request->input('longitude');
                $lat = $request->input('latitude');

                if (empty($long) || empty($lat)) {
                    throw new \Exception('Latitude and longitude are required');
                }

                $response = $essentialsUtil->getLocationFromCoordinates($lat, $long);

                if (! empty($response)) {
                    $data['clock_in_location'] = $response;
                }
            }

            $output = $essentialsUtil->clockin($data, $settings);

            if ($output['success']) {
                return $this->respond($output);
            } else {
                return $this->otherExceptions($output['msg']);
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->otherExceptions($e);
        }
    }

    /**
     * Clock Out
     *
     * [User must have "essentials.allow_users_for_attendance_from_api" permission to Clock out]
     *
     * @bodyParam user_id integer required id of the user Example: 1
     * @bodyParam clock_out_time string Clock out time.If not given current date time will be used Fromat: Y-m-d H:i:s Example:2000-06-13 13:13:00
     * @bodyParam clock_out_note string Clock out note.
     * @bodyParam latitude string Latitude of the clock out location.
     * @bodyParam longitude string Longitude of the clock out location.
     *
     * @response {
         "success":true,
         "msg":"Clocked Out successfully",
         "type":"clock_out"
     }
     */
    public function clockout(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $user = Auth::user();
            $business_id = $user->business_id;
            $business = Business::findOrFail($business_id);
            $settings = $business->essentials_settings;
            $settings = ! empty($settings) ? json_decode($settings, true) : [];

            $data = [
                'business_id' => $business_id,
                // Match clock-in behavior: default to authenticated user.
                'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : $user->id,
                'clock_out_time' => empty($request->input('clock_out_time')) ? \Carbon::now() : $request->input('clock_out_time'),
                // Support mobile payload key `clock_in_note` as an alias for clock_out_note.
                'clock_out_note' => $request->input('clock_out_note', $request->input('clock_in_note')),
            ];

            $essentialsUtil = new \Modules\Essentials\Utils\EssentialsUtil;

            if ($request->hasFile('clockout_image')) {
                $path = $request->file('clockout_image')->store('clock_out_images', 'public');
                if (! empty($path)) {
                    $data['clock_out_image'] = $path;
                }
            }

            if (! empty($settings['is_location_required'])) {
                $long = $request->input('longitude');
                $lat = $request->input('latitude');

                if (empty($long) || empty($lat)) {
                    throw new \Exception('Latitude and longitude are required');
                }

                $response = $essentialsUtil->getLocationFromCoordinates($lat, $long);

                if (! empty($response)) {
                    $data['clock_out_location'] = $response;
                }
            }

            $output = $essentialsUtil->clockout($data, $settings);

            if ($output['success']) {
                return $this->respond($output);
            } else {
                return $this->otherExceptions($output['msg']);
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->otherExceptions($e);
        }
    }

    /**
     * Verify / register mobile device fingerprint for the authenticated employee.
     *
     * @bodyParam dev_name string required Example: Pixel 8
     * @bodyParam dev_number string required Example: abc123-device-id
     */
    public function checkDevice(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();

        if (! $user->can('essentials.allow_users_for_attendance_from_api')) {
            return $this->respondUnauthorized();
        }

        $validator = Validator::make($request->all(), [
            'dev_name' => 'required|string|max:512',
            'dev_number' => 'required|string|max:512',
        ]);

        if ($validator->fails()) {
            return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
        }

        $business_id = $user->business_id;
        $devName = trim((string) $request->input('dev_name'));
        $devNumber = trim((string) $request->input('dev_number'));

        $record = EssentialsUserDevice::where('user_id', $user->id)
            ->where('business_id', $business_id)
            ->first();

        if ($record === null) {
            EssentialsUserDevice::create([
                'user_id' => $user->id,
                'business_id' => $business_id,
                'dev_name' => $devName,
                'dev_number' => $devNumber,
            ]);

            return $this->respond([
                'success' => true,
                'device_allowed' => true,
                'msg' => __('essentials::lang.device_registered_success'),
                'type' => 'device_check',
            ]);
        }

        $storedName = trim((string) $record->dev_name);
        $storedNumber = trim((string) $record->dev_number);

        if ($storedName === $devName && $storedNumber === $devNumber) {
            return $this->respond([
                'success' => true,
                'device_allowed' => true,
                'msg' => __('essentials::lang.device_verified_success'),
                'type' => 'device_check',
            ]);
        }

        return response()->json([
            'device_allowed' => false,
            'error' => [
                'message' => __('essentials::lang.device_not_registered_contact_admin'),
                'code' => 'DEVICE_NOT_REGISTERED',
            ],
        ], 400);
    }

    /**
     * Mobile home / dashboard summary (notifications, shift window, profile, attendance flags).
     *
     * @response {
     *   "data": {
     *     "new_notifications": 0,
     *     "work_day_start": "12:00 PM",
     *     "work_day_end": "08:00 PM",
     *     "business_name": "Acme",
     *     "request": null,
     *     "full_name": "John Doe",
     *     "image": null,
     *     "work": "Developer",
     *     "user_type": "employee",
     *     "signed_in": false,
     *     "signed_out": false
     *   }
     * }
     */
    public function home(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $user->loadMissing(['media']);

        $business_id = (int) $user->business_id;
        $business = Business::findOrFail($business_id);

        $essentialsUtil = new \Modules\Essentials\Utils\EssentialsUtil();
        $shift = $essentialsUtil->getTodayScheduledShiftForDisplay((int) $user->id, $business_id);

        $work_day_start = null;
        $work_day_end = null;
        if ($shift !== null) {
            $work_day_start = $this->formatApiShiftTime($shift['start_time'] ?? null, $business);
            $work_day_end = $this->formatApiShiftTime($shift['end_time'] ?? null, $business);
        }

        $work = '';
        if (! empty($user->essentials_designation_id)) {
            $designation = Category::find($user->essentials_designation_id);
            $work = $designation && ! empty($designation->name) ? $designation->name : '';
        }

        $image = null;
        if ($user->media && ! empty($user->media->display_url)) {
            $image = $user->media->display_url;
        }

        $new_notifications = (int) $user->unreadNotifications()->count();

        // Attendance flags should reflect today's status only.
        $today = \Carbon\Carbon::now()->toDateString();

        $signed_in = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user->id)
            ->whereDate('clock_in_time', $today)
            ->whereNull('clock_out_time')
            ->exists();

        $has_completed_today = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user->id)
            ->whereDate('clock_in_time', $today)
            ->whereNotNull('clock_out_time')
            ->exists();

        $signed_out = ! $signed_in && $has_completed_today;

        $user_type = (string) $user->user_type;
        if ($user_type === 'user') {
            $user_type = 'employee';
        }

        return response()->json([
            'data' => [
                'new_notifications' => $new_notifications,
                'work_day_start' => $work_day_start,
                'work_day_end' => $work_day_end,
                'business_name' => $business->name,
                'request' => null,
                'full_name' => trim($user->user_full_name),
                'image' => $image,
                'work' => $work,
                'user_type' => $user_type,
                'signed_in' => $signed_in,
                'signed_out' => $signed_out,
            ],
        ]);
    }

    /**
     * @param  mixed  $time
     */
    protected function formatApiShiftTime($time, Business $business): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        try {
            $c = \Carbon\Carbon::parse($time);
        } catch (\Throwable $e) {
            return null;
        }

        $fmt = ((int) $business->time_format === 12) ? 'h:i A' : 'H:i';

        return $c->format($fmt);
    }

    /**
     * List Holidays
     *
     * @queryParam location_id id of the location Example: 1
     * @queryParam start_date format:Y-m-d Example: 2020-06-25
     * @queryParam end_date format:Y-m-d Example: 2020-06-25
     *
     * @response {
            "data": [
                {
                    "id": 2,
                    "name": "Independence Day",
                    "start_date": "2020-08-15",
                    "end_date": "2020-09-15",
                    "business_id": 1,
                    "location_id": null,
                    "note": "test holiday",
                    "created_at": "2020-09-15 11:25:56",
                    "updated_at": "2020-09-15 11:25:56"
                }
            ]
        }
     */
    public function getHolidays()
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();
        $business_id = $user->business_id;

        $query = \Modules\Essentials\Entities\EssentialsHoliday::where('business_id', $business_id);

        $permitted_locations = $user->permitted_locations($business_id);
        if ($permitted_locations != 'all') {
            $query->where(function ($q) use ($permitted_locations) {
                $q->whereIn('location_id', $permitted_locations)
                    ->orWhereNull('location_id');
            });
        }

        if (! empty(request()->input('location_id'))) {
            $query->where('location_id', request()->input('location_id'));
        }

        if (! empty(request()->start_date) && ! empty(request()->end_date)) {
            $start = request()->start_date;
            $end = request()->end_date;
            $query->whereDate('start_date', '>=', $start)
                        ->whereDate('start_date', '<=', $end);
        }
        $holidays = $query->get();

        return new CommonResource($holidays);
    }
}
