<?php

namespace Modules\Essentials\Utils;

use App\BusinessLocation;
use App\Transaction;
use App\User;
use App\Utils\Util;
use DB;
use Illuminate\Support\Facades\View;
use Modules\Essentials\Entities\EssentialsAllowanceAndDeduction;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsUserShift;
use Modules\Essentials\Entities\Shift;
use Modules\Essentials\Entities\EssentialsHoliday;


class EssentialsUtil extends Util
{
    /**
     * Function to calculate total work duration of a user for a period of time
     *
     * @param  string  $unit
     * @param  int  $user_id
     * @param  int  $business_id
     * @param  int  $start_date = null
     * @param  int  $end_date = null
     */
    public function getTotalWorkDuration(
        $unit,
        $user_id,
        $business_id,
        $start_date = null,
        $end_date = null
    ) {
        $total_work_duration = 0;
        if ($unit == 'hour') {
            $query = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('user_id', $user_id)
                                        ->whereNotNull('clock_out_time');

            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate('clock_in_time', '>=', $start_date)
                            ->whereDate('clock_in_time', '<=', $end_date);
            }

            $minutes_sum = $query->select(DB::raw('SUM(TIMESTAMPDIFF(MINUTE, clock_in_time, clock_out_time)) as total_minutes'))->first();
            $total_work_duration = ! empty($minutes_sum->total_minutes) ? $minutes_sum->total_minutes / 60 : 0;
        }

        return number_format($total_work_duration, 2);
    }

    /**
     * Parses month and year from date
     *
     * @param  string  $month_year
     */
    public function getDateFromMonthYear($month_year)
    {
        $month_year_arr = explode('/', $month_year);
        $month = $month_year_arr[0];
        $year = $month_year_arr[1];

        $transaction_date = $year.'-'.$month.'-01';

        return $transaction_date;
    }

    /**
     * Retrieves all allowances and deductions of an employeee
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  string  $start_date = null
     * @param  string  $end_date = null
     */
    public function getEmployeeAllowancesAndDeductions($business_id, $user_id, $start_date = null, $end_date = null)
    {
        $query = EssentialsAllowanceAndDeduction::join('essentials_user_allowance_and_deductions as euad', 'euad.allowance_deduction_id', '=', 'essentials_allowances_and_deductions.id')
                ->where('business_id', $business_id)
                ->where('euad.user_id', $user_id);

        //Filter if applicable one
        if (! empty($start_date) && ! empty($end_date)) {
            $query->where(function ($q) use ($start_date, $end_date) {
                $q->whereNull('applicable_date')
                    ->orWhereBetween('applicable_date', [$start_date, $end_date]);
            });
        }
        $allowances_and_deductions = $query->get();

        return $allowances_and_deductions;
    }

    /**
     * Validates user clock in and returns available shift id
     */
    public function checkUserShift($user_id, $settings, $clock_in_time = null)
    {
        $shift_id = null;
        $clock_in_datetime = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time) : \Carbon::now();
        $clock_in_date = $clock_in_datetime->format('Y-m-d');
        $clock_in_time = $clock_in_datetime->format('H:i');
        
        $day_string = strtolower($clock_in_datetime->format('l'));
        $grace_before_checkin = ! empty($settings['grace_before_checkin']) ? (int) $settings['grace_before_checkin'] : 0;
        $grace_after_checkin = ! empty($settings['grace_after_checkin']) ? (int) $settings['grace_after_checkin'] : 0;
        
        //$clock_in_start = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->subMinutes($grace_before_checkin) : \Carbon::now()->subMinutes($grace_before_checkin);
        //$clock_in_end = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->addMinutes($grace_after_checkin) : \Carbon::now()->addMinutes($grace_after_checkin);

        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
                    ->where('user_id', $user_id)
                    ->where('start_date', '<=', $clock_in_date)
                    ->where(function ($q) use ($clock_in_date) {
                        $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $clock_in_date);
                    })
                    ->select('essentials_user_shifts.*', 's.holidays', 's.start_time', 's.end_time', 's.type')
                    ->get();

                    
        foreach ($user_shifts as $shift) {
            $holidays = json_decode($shift->holidays, true);
            //check if holiday
            if (is_array($holidays) && in_array($day_string, $holidays)) {
                continue;
            }

            //Check allocated shift time
            if (! empty($shift->start_time)) {

                $start_start_time = \Carbon::parse($shift->start_time)->subMinutes($grace_before_checkin);
                $start_end_time = \Carbon::parse($shift->start_time)->addMinutes($grace_after_checkin);

                if(\Carbon::parse($clock_in_time)->between($start_start_time, $start_end_time)){
                    return $shift->essentials_shift_id;
                }
            }

            if ($shift->type == 'flexible_shift') {
                return $shift->essentials_shift_id;
            }
        }

        return $shift_id;
    }

    /**
     * Validates user clock out
     */
    public function canClockOut($clock_in, $settings, $clock_out_time = null)
    {
        $shift = Shift::find($clock_in->essentials_shift_id);
        if (empty($shift->end_time)) {
            return true;
        }
        $grace_before_checkout = ! empty($settings['grace_before_checkout']) ? (int) $settings['grace_before_checkout'] : 0;
        $grace_after_checkout = ! empty($settings['grace_after_checkout']) ? (int) $settings['grace_after_checkout'] : 0;

        if ($shift->type != 'flexible_shift') {

            $end_start_time = \Carbon::parse($shift->end_time)->subMinutes($grace_before_checkout);
            $end_end_time = \Carbon::parse($shift->end_time)->addMinutes($grace_after_checkout);

            if(\Carbon::parse($clock_out_time)->between($end_start_time, $end_end_time)){
                return true;
            }
        } elseif ($shift->type == 'flexible_shift') {
            return true;
        } else {
            return false;
        }
    }

    public function clockin($data, $essentials_settings)
    {
        //Check user can clockin
        $clock_in_time = is_object($data['clock_in_time']) ? $data['clock_in_time']->toDateTimeString() : $data['clock_in_time'];

        $shift = $this->checkUserShift($data['user_id'], $essentials_settings, $clock_in_time);

        if (empty($shift)) {
            $available_shifts = $this->getAllAvailableShiftsForGivenUser($data['business_id'], $data['user_id']);

            $available_shifts_html = view('essentials::attendance.avail_shifts')
                                        ->with(compact('available_shifts'))
                                        ->render();

            $output = ['success' => false,
                'msg' => __('essentials::lang.shift_not_allocated'),
                'type' => 'clock_in',
                'shift_details' => $available_shifts_html,
            ];

            return $output;
        }

        $data['essentials_shift_id'] = $shift;

        //Check if already clocked in
        $count = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->count();
        if ($count == 0) {
            EssentialsAttendance::create($data);

            $shift_info = Shift::getGivenShiftInfo($data['business_id'], $shift);
            $current_shift_html = view('essentials::attendance.current_shift')
                                    ->with(compact('shift_info'))
                                    ->render();

            $output = ['success' => true,
                'msg' => __('essentials::lang.clock_in_success'),
                'type' => 'clock_in',
                'current_shift' => $current_shift_html,
            ];
        } else {
            $output = ['success' => false,
                'msg' => __('essentials::lang.already_clocked_in'),
                'type' => 'clock_in',
            ];
        }

        return $output;
    }

    public function clockout($data, $essentials_settings)
    {

        //Get clock in
        $clock_in = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->first();
        $clock_out_time = is_object($data['clock_out_time']) ? $data['clock_out_time']->toDateTimeString() : $data['clock_out_time'];

        if (! empty($clock_in)) {
            $can_clockout = $this->canClockOut($clock_in, $essentials_settings, $clock_out_time);
            if (! $can_clockout) {
                $output = ['success' => false,
                    'msg' => __('essentials::lang.shift_not_over'),
                    'type' => 'clock_out',
                ];

                return $output;
            }

            $clock_in->clock_out_time = $data['clock_out_time'];
            $clock_in->clock_out_note = $data['clock_out_note'];
            $clock_in->clock_out_location = $data['clock_out_location'] ?? '';
            $clock_in->save();

            $output = ['success' => true,
                'msg' => __('essentials::lang.clock_out_success'),
                'type' => 'clock_out',
            ];
        } else {
            $output = ['success' => false,
                'msg' => __('essentials::lang.not_clocked_in'),
                'type' => 'clock_out',
            ];
        }

        return $output;
    }

    public function getAllAvailableShiftsForGivenUser($business_id, $user_id)
    {
        $available_user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=',
                                    'essentials_user_shifts.essentials_shift_id')
                                    ->where('user_id', $user_id)
                                    ->where('s.business_id', $business_id)
                                    ->whereDate('start_date', '<=', \Carbon::today())
                                    ->whereDate('end_date', '>=', \Carbon::today())
                                    ->select('essentials_user_shifts.start_date', 'essentials_user_shifts.end_date',
                                        's.name', 's.type', 's.start_time', 's.end_time', 's.holidays')
                                    ->get();

        return $available_user_shifts;
    }

    /**
     * get total leaves of and employee for given date
     *
     * @param  int  $business_id
     * @param  int  $employee_id
     * @param  string  $start_date
     * @param  string  $end_date
     */
    public function getTotalLeavesForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $leaves = EssentialsLeave::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereDate('start_date', '>=', $start_date)
                        ->whereDate('end_date', '<=', $end_date)
                        ->get();

        $total_leaves = 0;
        foreach ($leaves as $key => $leave) {
            $start_date = \Carbon::parse($leave->start_date);
            $end_date = \Carbon::parse($leave->end_date);

            $diff = $start_date->diffInDays($end_date);
            $diff += 1;
            $total_leaves += $diff;
        }

        return $total_leaves;
    }

    public function getTotalDaysWorkedForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $attendances = EssentialsAttendance::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereNotNull('clock_out_time')
                        ->whereDate('clock_in_time', '>=', $start_date)
                        ->whereDate('clock_in_time', '<=', $end_date)
                        ->get()
                        ->groupBy(function ($attendance, $key) {
                            return \Carbon::parse($attendance->clock_in_time)->format('Y-m-d');
                        });

        return count($attendances);
    }

    public function getPayrollQuery($business_id)
    {
        $payrolls = Transaction::where('transactions.business_id', $business_id)
                    ->where('type', 'payroll')
                    ->join('users as u', 'u.id', '=', 'transactions.expense_for')
                    ->leftJoin('categories as dept', 'u.essentials_department_id', '=', 'dept.id')
                    ->leftJoin('categories as dsgn', 'u.essentials_designation_id', '=', 'dsgn.id')
                    ->leftJoin('essentials_payroll_group_transactions as epgt', 'transactions.id', '=', 'epgt.transaction_id')
                    ->leftJoin('essentials_payroll_groups as epg', 'epgt.payroll_group_id', '=', 'epg.id')
                    ->select([
                        'transactions.id',
                        DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user"),
                        'final_total',
                        'transaction_date',
                        'ref_no',
                        'transactions.payment_status',
                        'dept.name as department',
                        'dsgn.name as designation',
                        'epgt.payroll_group_id',
                    ]);

        return $payrolls;
    }

    public function getEssentialsSettings()
    {
        $settings = request()->session()->get('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];

        return $settings;
    }

    public function Gettotalholiday($business_id, $location, $start_date, $end_date, $permitted_locations){
        $holidays = EssentialsHoliday::where('essentials_holidays.business_id', $business_id)
                        ->leftJoin('business_locations as bl', 'bl.id', '=', 'essentials_holidays.location_id')
                        ->select([
                            'essentials_holidays.id',
                            'essentials_holidays.name',
                            'bl.name as location',
                            'start_date',
                            'end_date',
                            'note',
                        ]);

            if ($permitted_locations != 'all') {
                $holidays->where(function ($query) use ($permitted_locations) {
                    $query->whereIn('essentials_holidays.location_id', $permitted_locations)
                        ->orWhereNull('essentials_holidays.location_id');
                });
            }

            if (! empty($location)) {
                $holidays->where('essentials_holidays.location_id', $location);
            }

            if (! empty($start_date) && ! empty($end_date)) {
                $holidays->whereDate('essentials_holidays.start_date', '>=', $start_date)
                            ->whereDate('essentials_holidays.start_date', '<=', $end_date);
            }

            return $holidays;
    }

    /**
     * Full month attendance calendar for API/mobile: matches connector getAttendanceByDate payload shape.
     * Weekend: Friday & Saturday (GCC). Calendar day status: 4 = workday, 6 = weekend.
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  int  $year
     * @param  int  $month  1–12
     * @param  array<string, mixed>  $essentials_settings
     * @param  array<int>|string  $permitted_locations  Business location ids or 'all'
     * @return array<string, mixed>
     */
    public function getAttendanceCalendarByMonth(
        $business_id,
        $user_id,
        $year,
        $month,
        $essentials_settings = [],
        $permitted_locations = 'all'
    ) {
        $year = (int) $year;
        $month = (int) $month;
        $monthStart = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $extendedStart = $monthStart->copy()->subDays(7);
        $extendedEnd = $monthEnd->copy()->addDays(7);

        $attendanceRows = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereDate('clock_in_time', '>=', $extendedStart->toDateString())
            ->whereDate('clock_in_time', '<=', $extendedEnd->toDateString())
            ->orderBy('clock_in_time')
            ->get();

        $byDate = [];
        foreach ($attendanceRows as $row) {
            $key = \Carbon\Carbon::parse($row->clock_in_time)->format('Y-m-d');
            if (! isset($byDate[$key])) {
                $byDate[$key] = [];
            }
            $byDate[$key][] = $row;
        }

        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $extendedEnd->toDateString())
            ->whereDate('end_date', '>=', $extendedStart->toDateString())
            ->get();

        $holidayQuery = EssentialsHoliday::where('business_id', $business_id)
            ->whereDate('start_date', '<=', $extendedEnd->toDateString())
            ->whereDate('end_date', '>=', $extendedStart->toDateString());

        if ($permitted_locations !== 'all' && is_array($permitted_locations) && count($permitted_locations) > 0) {
            $holidayQuery->where(function ($q) use ($permitted_locations) {
                $q->whereIn('location_id', $permitted_locations)
                    ->orWhereNull('location_id');
            });
        }

        $holidays = $holidayQuery->get();

        $graceAfterCheckin = (int) ($essentials_settings['grace_after_checkin'] ?? 0);
        $graceAfterCheckout = (int) ($essentials_settings['grace_after_checkout'] ?? 0);

        $attended = $late = $absent = $vacation = $weekend = $no_clockout = $out = 0;
        $totalLateMinutes = $totalOvertimeMinutes = 0;

        $buildDayStrip = function (\Carbon\Carbon $date) use (
            &$byDate,
            $business_id,
            $user_id,
            $graceAfterCheckin,
            $graceAfterCheckout,
        ) {
            $dateStr = $date->format('Y-m-d');
            $isWeekend = $date->isFriday() || $date->isSaturday();
            $calendarStatus = $isWeekend ? 6 : 4;

            $rows = $byDate[$dateStr] ?? [];
            $clockInNote = '';
            $startTime = null;
            $endTime = null;
            $lateMinutes = 0;
            $overtimeMinutes = 0;

            if (! empty($rows)) {
                usort($rows, function ($a, $b) {
                    return strcmp($a->clock_in_time, $b->clock_in_time);
                });
                $first = $rows[0];
                $lastWithOut = collect($rows)->filter(function ($r) {
                    return ! empty($r->clock_out_time);
                })->sortByDesc('clock_out_time')->first();

                $clockInNote = (string) ($first->clock_in_note ?? '');
                $startTime = $first->clock_in_time;
                $endTime = $lastWithOut ? $lastWithOut->clock_out_time : null;

                $shift = $this->getUserShiftForDate($business_id, $user_id, $date);
                if (! empty($shift) && ! empty($shift->start_time) && ($shift->type ?? '') !== 'flexible_shift') {
                    try {
                        $scheduledStart = \Carbon\Carbon::parse($dateStr.' '.$this->normalizeTimeString($shift->start_time));
                        $deadline = $scheduledStart->copy()->addMinutes($graceAfterCheckin);
                        $clockIn = \Carbon\Carbon::parse($first->clock_in_time);
                        if ($clockIn->gt($deadline)) {
                            $lateMinutes = $clockIn->diffInMinutes($deadline);
                        }
                    } catch (\Exception $e) {
                        $lateMinutes = 0;
                    }
                }

                if (! empty($shift) && ! empty($shift->end_time) && ($shift->type ?? '') !== 'flexible_shift' && $lastWithOut) {
                    try {
                        $scheduledEnd = \Carbon\Carbon::parse($dateStr.' '.$this->normalizeTimeString($shift->end_time));
                        $overtimeThreshold = $scheduledEnd->copy()->addMinutes($graceAfterCheckout);
                        $clockOut = \Carbon\Carbon::parse($lastWithOut->clock_out_time);
                        if ($clockOut->gt($overtimeThreshold)) {
                            $overtimeMinutes = $clockOut->diffInMinutes($overtimeThreshold);
                        }
                    } catch (\Exception $e) {
                        $overtimeMinutes = 0;
                    }
                }
            }

            return [
                'number_in_month' => (int) $date->format('j'),
                'number_in_week' => (int) $date->format('w') + 1,
                'month' => (int) $date->format('n'),
                'year' => (int) $date->format('Y'),
                'name' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][(int) $date->format('w')],
                'status' => $calendarStatus,
                'clock_in_note' => $clockInNote,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'late_minutes' => $lateMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ];
        };

        $daysBefore = [];
        for ($d = $monthStart->copy()->subDays(7); $d->lt($monthStart); $d->addDay()) {
            $daysBefore[] = $buildDayStrip($d->copy());
        }

        $days = [];
        for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
            $dayData = $buildDayStrip($d->copy());
            $days[] = $dayData;

            $dateStr = $d->format('Y-m-d');
            $isWeekend = $d->isFriday() || $d->isSaturday();

            if ($isWeekend) {
                $weekend++;
            }

            $onApprovedLeave = $leaves->contains(function ($leave) use ($dateStr) {
                return $dateStr >= $leave->start_date && $dateStr <= $leave->end_date;
            });

            $onHoliday = $holidays->contains(function ($h) use ($d) {
                $start = \Carbon\Carbon::parse($h->start_date)->startOfDay();
                $end = \Carbon\Carbon::parse($h->end_date)->endOfDay();

                return $d->between($start, $end);
            });

            $rows = $byDate[$dateStr] ?? [];

            if ($isWeekend || $onHoliday) {
                continue;
            }

            if ($onApprovedLeave) {
                $vacation++;

                continue;
            }

            if (empty($rows)) {
                $absent++;

                continue;
            }

            if ($this->attendanceRowsMissingClockOut($rows)) {
                $no_clockout++;
            }

            $shift = $this->getUserShiftForDate($business_id, $user_id, $d->copy());
            $firstRow = collect($rows)->sortBy('clock_in_time')->first();
            $isLate = false;
            if (! empty($shift) && ! empty($shift->start_time) && ($shift->type ?? '') !== 'flexible_shift') {
                try {
                    $deadline = \Carbon\Carbon::parse($dateStr.' '.$this->normalizeTimeString($shift->start_time))
                        ->addMinutes($graceAfterCheckin);
                    if (\Carbon\Carbon::parse($firstRow->clock_in_time)->gt($deadline)) {
                        $isLate = true;
                    }
                } catch (\Exception $e) {
                    $isLate = false;
                }
            }

            if ($isLate) {
                $late++;
                try {
                    $deadline = \Carbon\Carbon::parse($dateStr.' '.$this->normalizeTimeString($shift->start_time))
                        ->addMinutes($graceAfterCheckin);
                    $totalLateMinutes += $deadline->diffInMinutes(\Carbon\Carbon::parse($firstRow->clock_in_time));
                } catch (\Exception $e) {
                }
            } else {
                $attended++;
            }

            $lastOut = collect($rows)->filter(function ($r) {
                return ! empty($r->clock_out_time);
            })->sortByDesc('clock_out_time')->first();

            if ($lastOut && ! empty($shift) && ! empty($shift->end_time) && ($shift->type ?? '') !== 'flexible_shift') {
                try {
                    $threshold = \Carbon\Carbon::parse($dateStr.' '.$this->normalizeTimeString($shift->end_time))
                        ->addMinutes($graceAfterCheckout);
                    $clockOut = \Carbon\Carbon::parse($lastOut->clock_out_time);
                    if ($clockOut->gt($threshold)) {
                        $totalOvertimeMinutes += $clockOut->diffInMinutes($threshold);
                    }
                } catch (\Exception $e) {
                }
            }
        }

        $daysAfter = [];
        for ($d = $monthEnd->copy()->addDay(); $d->lte($monthEnd->copy()->addDays(7)); $d->addDay()) {
            $daysAfter[] = $buildDayStrip($d->copy());
        }

        return [
            'attended' => $attended,
            'late' => $late,
            'absent' => $absent,
            'out' => $out,
            'vacation' => $vacation,
            'weekend' => $weekend,
            'no_clockout' => $no_clockout,
            'total_late_minutes' => $totalLateMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'month_name' => [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ][$month] ?? $monthStart->format('F'),
            'days_before' => $daysBefore,
            'days' => $days,
            'days_after' => $daysAfter,
        ];
    }

    /**
     * @param  array<int, EssentialsAttendance>  $rows
     */
    protected function attendanceRowsMissingClockOut(array $rows): bool
    {
        foreach ($rows as $r) {
            if (empty($r->clock_out_time)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Shift|null
     */
    protected function getUserShiftForDate($business_id, $user_id, \Carbon\Carbon $date)
    {
        $dateStr = $date->format('Y-m-d');

        return Shift::join('essentials_user_shifts as us', 'us.essentials_shift_id', '=', 'essentials_shifts.id')
            ->where('us.user_id', $user_id)
            ->where('essentials_shifts.business_id', $business_id)
            ->whereDate('us.start_date', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('us.end_date')
                    ->orWhereDate('us.end_date', '>=', $dateStr);
            })
            ->select('essentials_shifts.*')
            ->first();
    }

    protected function normalizeTimeString($time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }
        $str = (string) $time;

        return strlen($str) === 5 ? $str.':00' : $str;
    }

    /**
     * Resolves the business location for API clock-in (optional explicit branch id, else first branch).
     */
    protected function resolveClockInBusinessLocation(int $businessId, $locationId): ?BusinessLocation
    {
        $q = BusinessLocation::where('business_id', $businessId);
        if ($locationId !== null && $locationId !== '') {
            return $q->where('id', (int) $locationId)->first();
        }

        return $q->orderBy('id')->first();
    }

    /**
     * API clock-in: if branch has a geofence, position must be inside, unless a non-empty note is provided.
     *
     * @return array{message: string, code: string, status: int}|null
     */
    public function validateApiClockInGeofence(int $businessId, User $user, $latitude, $longitude, $clockInNote, $locationId): ?array
    {
        $hasExplicitLocation = $locationId !== null && $locationId !== '';
        $bl = $this->resolveClockInBusinessLocation($businessId, $locationId);
        if ($hasExplicitLocation && $bl === null) {
            return [
                'message' => __('essentials::lang.business_location_not_found'),
                'code' => 'BUSINESS_LOCATION_NOT_FOUND',
                'status' => 404,
            ];
        }
        if ($bl === null) {
            return null;
        }

        $permitted = $user->permitted_locations($businessId);
        if ($permitted !== 'all' && ! in_array($bl->id, $permitted, true)) {
            return [
                'message' => __('essentials::lang.business_location_not_permitted'),
                'code' => 'BUSINESS_LOCATION_NOT_PERMITTED',
                'status' => 403,
            ];
        }

        if (! $bl->hasActiveAttendanceGeofence()) {
            return null;
        }
        if ($latitude === null || $latitude === '' || $longitude === null || $longitude === '') {
            return [
                'message' => __('essentials::lang.attendance_geofence_coordinates_required'),
                'code' => 'GEOFENCE_COORDINATES_REQUIRED',
                'status' => 400,
            ];
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;
        $note = is_string($clockInNote) ? trim($clockInNote) : (string) $clockInNote;
        if ($note === '') {
            $note = '';
        }

        if (! $bl->isCoordinateInsideAttendanceGeofence($lat, $lng) && $note === '') {
            return [
                'message' => __('essentials::lang.outside_attendance_geofence'),
                'code' => 'OUTSIDE_GEOFENCE',
                'status' => 400,
            ];
        }

        return null;
    }
}
