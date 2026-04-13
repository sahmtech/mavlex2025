<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\BankReconciliation;
use Modules\Accounting\Entities\BankReconciliationItem;

class BankReconciliationController extends Controller
{
    public function __construct(protected ModuleUtil $moduleUtil) {}

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeBank($business_id);

        $rows = BankReconciliation::with('account')
            ->where('business_id', $business_id)
            ->orderByDesc('statement_date')
            ->paginate(30);

        return view('accounting::bank_reconciliation.index', compact('rows'));
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeBank($business_id);

        $accounts = AccountingAccount::where('business_id', $business_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('accounting::bank_reconciliation.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeBank($business_id);

        $data = $request->validate([
            'accounting_account_id' => 'required|exists:accounting_accounts,id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'notes' => 'nullable|string',
        ]);

        $account = AccountingAccount::where('business_id', $business_id)
            ->where('id', $data['accounting_account_id'])
            ->firstOrFail();

        $book = $this->bookBalanceAt($account->id, $data['statement_date']);

        BankReconciliation::create([
            'business_id' => $business_id,
            'accounting_account_id' => $account->id,
            'statement_date' => $data['statement_date'],
            'statement_balance' => $data['statement_balance'],
            'book_balance' => $book,
            'status' => 'open',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->action([self::class, 'index'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeBank($business_id);

        $recon = BankReconciliation::where('business_id', $business_id)->findOrFail($id);
        $this->syncItems($recon);

        $items = BankReconciliationItem::with('glLine')
            ->where('reconciliation_id', $recon->id)
            ->get();

        $cleared = BankReconciliationItem::where('reconciliation_id', $recon->id)->where('is_cleared', true)->get();
        $clearedSum = 0.0;
        foreach ($cleared as $c) {
            $line = $c->glLine;
            if ($line) {
                $clearedSum += $line->type === 'debit' ? (float) $line->amount : -1 * (float) $line->amount;
            }
        }

        return view('accounting::bank_reconciliation.show', compact('recon', 'items', 'clearedSum'));
    }

    public function updateItems(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeBank($business_id);

        $recon = BankReconciliation::where('business_id', $business_id)->findOrFail($id);
        $request->validate([
            'cleared' => 'nullable|array',
            'cleared.*' => 'integer|exists:accounting_bank_reconciliation_items,id',
        ]);

        $clearedIds = $request->input('cleared', []);
        DB::transaction(function () use ($recon, $clearedIds) {
            BankReconciliationItem::where('reconciliation_id', $recon->id)->update(['is_cleared' => false]);
            if ($clearedIds !== []) {
                BankReconciliationItem::where('reconciliation_id', $recon->id)
                    ->whereIn('id', $clearedIds)
                    ->update(['is_cleared' => true]);
            }
        });

        return redirect()
            ->action([self::class, 'show'], $recon->id)
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.updated_success')]);
    }

    public function complete($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeBank($business_id);

        $recon = BankReconciliation::where('business_id', $business_id)->findOrFail($id);
        $recon->status = 'closed';
        $recon->save();

        return redirect()
            ->action([self::class, 'index'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.updated_success')]);
    }

    private function syncItems(BankReconciliation $recon): void
    {
        $lines = AccountingAccountsTransaction::where('accounting_account_id', $recon->accounting_account_id)
            ->whereDate('operation_date', '<=', $recon->statement_date)
            ->orderBy('operation_date')
            ->get();

        foreach ($lines as $line) {
            BankReconciliationItem::firstOrCreate(
                [
                    'reconciliation_id' => $recon->id,
                    'gl_line_id' => $line->id,
                ],
                ['is_cleared' => false]
            );
        }
    }

    private function bookBalanceAt(int $accountId, string $statementDate): float
    {
        $lines = AccountingAccountsTransaction::where('accounting_account_id', $accountId)
            ->whereDate('operation_date', '<=', $statementDate)
            ->get();

        $sum = 0.0;
        foreach ($lines as $line) {
            $sum += $line->type === 'debit' ? (float) $line->amount : -1 * (float) $line->amount;
        }

        return round($sum, 4);
    }

    private function authorizeBank($business_id): void
    {
        if (
            ! (auth()->user()->can('superadmin')
                || auth()->user()->can('Admin#'.$business_id)
                || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')
                    && auth()->user()->can('accounting.bank_reconciliation')))
        ) {
            abort(403, 'Unauthorized action.');
        }
    }
}
