<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Business;
use App\Transaction;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Modules\Essentials\Entities\ToDo;

/**
 * @group Essentials (mobile)
 * @authenticated
 *
 * APIs for essentials mobile dashboard & payroll.
 */
class EssentialsController extends ApiController
{
    /**
     * @var \App\Utils\ModuleUtil
     */
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Get Payroll Details (Essentials)
     *
     * Returns payrolls for the given year up to the given month (inclusive).
     *
     * @queryParam year int required Example: 2026
     * @queryParam month int required Example: 4
     */
    public function getPayrollDetails(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);
        if ($validator->fails()) {
            return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
        }

        $year = (int) $request->query('year');
        $month = (int) $request->query('month');

        $user = Auth::user();
        $business_id = (int) $user->business_id;

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            return $this->respondUnauthorized();
        }

        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'payroll')
            ->whereYear('transactions.transaction_date', $year)
            ->whereMonth('transactions.transaction_date', '<=', $month)
            ->orderBy('transactions.transaction_date');

        if (! $user->can('essentials.view_all_payroll')) {
            $query->where('transactions.expense_for', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('transactions.expense_for', (int) $request->query('user_id'));
        }

        $rows = $query->get([
            'transactions.total_before_tax',
            'transactions.final_total',
            'transactions.essentials_amount_per_unit_duration',
            'transactions.exchange_rate',
            'transactions.essentials_allowances',
            'transactions.essentials_deductions',
            'transactions.transaction_date',
        ]);

        $data = [];
        foreach ($rows as $row) {
            $dt = \Carbon\Carbon::parse($row->transaction_date);

            $allowances = ! empty($row->essentials_allowances) ? json_decode($row->essentials_allowances, true) : [];
            $deductions = ! empty($row->essentials_deductions) ? json_decode($row->essentials_deductions, true) : [];

            $data[] = [
                'payroll' => [
                    'total_before_tax' => $row->total_before_tax ?? 0,
                    'final_total' => (string) ($row->final_total ?? '0'),
                    'essentials_amount_per_unit_duration' => (string) ($row->essentials_amount_per_unit_duration ?? '0'),
                    'exchange_rate' => (string) ($row->exchange_rate ?? '1.000'),
                ],
                'year' => (string) $dt->format('Y'),
                'allowances' => [
                    'allowance_names' => $allowances['allowance_names'] ?? [],
                    'allowance_amounts' => $allowances['allowance_amounts'] ?? [],
                    'allowance_types' => $allowances['allowance_types'] ?? [],
                    'allowance_percents' => $allowances['allowance_percents'] ?? ($allowances['allowance_percent'] ?? []),
                ],
                'deductions' => [
                    'deduction_names' => $deductions['deduction_names'] ?? [],
                    'deduction_amounts' => $deductions['deduction_amounts'] ?? [],
                    'deduction_types' => $deductions['deduction_types'] ?? [],
                    'deduction_percents' => $deductions['deduction_percents'] ?? ($deductions['deduction_percent'] ?? []),
                ],
                'month_name' => $dt->format('F'),
            ];
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * List current user's Essentials tasks with assignees, comments, and attachments.
     *
     * @queryParam priority string Filter by priority: low, medium, high, urgent.
     * @queryParam status string Filter by status: new, in_progress, on_hold, completed.
     * @queryParam start_date date Filter tasks with date >= start_date (Y-m-d).
     * @queryParam end_date date Filter tasks with date <= end_date (Y-m-d).
     */
    public function getMyToDo(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $payload = [
            'priority' => $request->query('priority'),
            'status' => $request->query('status'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ];
        foreach (['priority', 'status'] as $k) {
            if ($payload[$k] === '' || $payload[$k] === null) {
                $payload[$k] = null;
            }
        }

        $validator = Validator::make($payload, [
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'status' => 'nullable|string|in:new,in_progress,on_hold,completed',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
        }
        if (! empty($payload['start_date']) && ! empty($payload['end_date']) && $payload['start_date'] > $payload['end_date']) {
            return $this->setStatusCode(422)->respondWithError('The end_date must be on or after start_date.');
        }

        $user = Auth::user();
        $business_id = (int) $user->business_id;
        $auth_id = (int) $user->id;

        if (! ($user->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            return $this->respondUnauthorized();
        }

        $task_statuses = ToDo::getTaskStatus();
        $priorities = ToDo::getTaskPriorities();

        $query = ToDo::where('business_id', $business_id)
            ->where(function ($q) use ($auth_id) {
                $q->where('created_by', $auth_id)
                    ->orWhereHas('users', function ($uq) use ($auth_id) {
                        $uq->where('user_id', $auth_id);
                    });
            })
            ->with([
                'assigned_by:id,surname,first_name,last_name',
                'users:id,surname,first_name,last_name',
                'comments.added_by:id,surname,first_name,last_name',
                'comments.media',
                'media.uploaded_by_user:id,surname,first_name,last_name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (! empty($payload['priority'])) {
            $query->where('priority', $payload['priority']);
        }
        if (! empty($payload['status'])) {
            $query->where('status', $payload['status']);
        }
        if (! empty($payload['start_date']) && ! empty($payload['end_date'])) {
            $query->whereDate('date', '>=', $payload['start_date'])
                ->whereDate('date', '<=', $payload['end_date']);
        } elseif (! empty($payload['start_date'])) {
            $query->whereDate('date', '>=', $payload['start_date']);
        } elseif (! empty($payload['end_date'])) {
            $query->whereDate('date', '<=', $payload['end_date']);
        }

        $todos = $query->get();

        $data = $todos->map(function (ToDo $todo) use ($task_statuses, $priorities) {
            return $this->serializeTodoForApi($todo, $task_statuses, $priorities);
        })->values()->all();

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * @param  array<string, string>  $task_statuses
     * @param  array<string, string>  $priorities
     */
    protected function serializeTodoForApi(ToDo $todo, array $task_statuses, array $priorities): array
    {
        $formatUser = function ($u) {
            if (! $u) {
                return null;
            }

            return [
                'id' => (int) $u->id,
                'name' => $u->user_full_name,
            ];
        };

        $comments = $todo->comments->map(function ($c) use ($formatUser) {
            return [
                'id' => (int) $c->id,
                'comment' => $c->comment,
                'created_at' => $c->created_at ? $c->created_at->toIso8601String() : null,
                'updated_at' => $c->updated_at ? $c->updated_at->toIso8601String() : null,
                'comment_by' => $formatUser($c->added_by),
                'media' => $c->media->map(function ($m) {
                    return [
                        'id' => (int) $m->id,
                        'file_name' => $m->file_name,
                        'display_name' => $m->display_name,
                        'display_url' => $m->display_url,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $documents = $todo->media->map(function ($m) use ($formatUser) {
            return [
                'id' => (int) $m->id,
                'file_name' => $m->file_name,
                'display_name' => $m->display_name,
                'display_url' => $m->display_url,
                'uploaded_by' => $formatUser($m->uploaded_by_user),
            ];
        })->values()->all();

        $statusKey = $todo->status;
        $priorityKey = $todo->priority;

        return [
            'id' => (int) $todo->id,
            'business_id' => (int) $todo->business_id,
            'task' => $todo->task,
            'task_ref' => $todo->task_id,
            'description' => $todo->description,
            'date' => $todo->date ? \Carbon\Carbon::parse($todo->date)->toDateString() : null,
            'end_date' => $todo->end_date ? \Carbon\Carbon::parse($todo->end_date)->toDateString() : null,
            'status' => $statusKey,
            'status_label' => ! empty($statusKey) && isset($task_statuses[$statusKey]) ? $task_statuses[$statusKey] : null,
            'priority' => $priorityKey,
            'priority_label' => ! empty($priorityKey) && isset($priorities[$priorityKey]) ? $priorities[$priorityKey] : null,
            'estimated_hours' => $todo->estimated_hours,
            'created_at' => $todo->created_at ? $todo->created_at->toIso8601String() : null,
            'updated_at' => $todo->updated_at ? $todo->updated_at->toIso8601String() : null,
            'assigned_by' => $formatUser($todo->assigned_by),
            'assignees' => $todo->users->map(fn ($u) => $formatUser($u))->filter()->values()->all(),
            'comments' => $comments,
            'documents' => $documents,
        ];
    }
}

