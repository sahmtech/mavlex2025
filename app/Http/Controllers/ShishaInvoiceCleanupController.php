<?php

namespace App\Http\Controllers;

use App\Business;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ShishaInvoiceCleanupController extends Controller
{
    protected $transactionUtil;

    protected $businessUtil;

    protected $moduleUtil;

    public function __construct(
        TransactionUtil $transactionUtil,
        BusinessUtil $businessUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Upload form – access by typing URL only (no menu link).
     */
    public function index()
    {
        $this->authorizeAccess();

        return view('tools.shisha_invoice_cleanup.index');
    }

    /**
     * Parse Excel and preview matching invoices / lines.
     */
    public function preview(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $uploaded_file = $request->file('file');

        try {
            $parsed = $this->parseExcelRows($uploaded_file);
        } catch (\Maatwebsite\Excel\Exceptions\NoTypeDetectedException $e) {
            return redirect()
                ->action([self::class, 'index'])
                ->with('status', [
                    'success' => 0,
                    'msg' => 'تعذّر قراءة الملف. تأكد أن الامتداد xlsx أو xls أو csv ثم أعد الرفع.',
                ]);
        }

        if (empty($parsed['rows'])) {
            return redirect()
                ->action([self::class, 'index'])
                ->with('status', [
                    'success' => 0,
                    'msg' => 'ملف الإكسل فارغ أو لا يحتوي صفوف أصناف صالحة.',
                ]);
        }

        $preview = $this->buildPreview($business_id, $parsed['rows']);

        $token = (string) Str::uuid();
        $request->session()->put('shisha_cleanup_preview_'.$token, [
            'created_at' => now()->toDateTimeString(),
            'excel_rows' => $parsed['rows'],
            'actions' => $preview['actions'],
        ]);

        return view('tools.shisha_invoice_cleanup.preview', [
            'token' => $token,
            'excel_rows_count' => count($parsed['rows']),
            'unique_products' => $parsed['unique_products'],
            'invoices' => $preview['invoices'],
            'stats' => $preview['stats'],
            'unmatched_rows' => $preview['unmatched_rows'],
            'blocked' => $preview['blocked'],
        ]);
    }

    /**
     * Confirm and execute deletions.
     */
    public function confirm(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'token' => 'required|string',
            'confirm_text' => 'required|in:CONFIRM,تأكيد',
        ], [
            'confirm_text.in' => 'اكتب كلمة تأكيد أو CONFIRM للمتابعة.',
        ]);

        $token = $request->input('token');
        $sessionKey = 'shisha_cleanup_preview_'.$token;
        $payload = $request->session()->get($sessionKey);

        if (empty($payload) || empty($payload['actions'])) {
            return redirect()
                ->action([self::class, 'index'])
                ->with('status', [
                    'success' => 0,
                    'msg' => 'انتهت صلاحية المعاينة أو لا توجد عمليات للتنفيذ. أعد رفع الملف.',
                ]);
        }

        $business_id = $request->session()->get('user.business_id');
        $results = [
            'invoices_deleted' => 0,
            'lines_deleted' => 0,
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            foreach ($payload['actions'] as $action) {
                $transaction = Transaction::where('business_id', $business_id)
                    ->where('id', $action['transaction_id'])
                    ->where('type', 'sell')
                    ->with(['sell_lines'])
                    ->first();

                if (empty($transaction)) {
                    $results['errors'][] = 'فاتورة غير موجودة: '.$action['invoice_no'];
                    continue;
                }

                if ($this->isZatcaLocked($transaction)) {
                    $results['errors'][] = 'تم تخطي '.$action['invoice_no'].' (مزامنة زاتكا ناجحة).';
                    continue;
                }

                if (! empty($action['delete_invoice'])) {
                    if ($this->transactionUtil->isReturnExist($transaction->id)) {
                        $results['errors'][] = 'تم تخطي '.$action['invoice_no'].' لوجود مرتجع.';
                        continue;
                    }

                    $output = $this->transactionUtil->deleteSale($business_id, $transaction->id);
                    if (empty($output['success'])) {
                        $results['errors'][] = ($action['invoice_no']).': '.($output['msg'] ?? 'فشل حذف الفاتورة');
                        continue;
                    }
                    $results['invoices_deleted']++;
                    continue;
                }

                $line_ids = array_values(array_unique($action['sell_line_ids'] ?? []));
                if (empty($line_ids)) {
                    continue;
                }

                // Include modifier children of targeted lines
                $modifier_ids = TransactionSellLine::where('transaction_id', $transaction->id)
                    ->whereIn('parent_sell_line_id', $line_ids)
                    ->pluck('id')
                    ->toArray();
                $all_delete_ids = array_values(array_unique(array_merge($line_ids, $modifier_ids)));

                $status_before = $transaction->status;
                $business = Business::findOrFail($business_id);
                $business_data = [
                    'id' => $business_id,
                    'accounting_method' => $business->accounting_method,
                    'location_id' => $transaction->location_id,
                ];

                $this->transactionUtil->deleteSellLines($all_delete_ids, $transaction->location_id, true);
                $this->transactionUtil->adjustMappingPurchaseSell(
                    $status_before,
                    $transaction->fresh(['sell_lines']),
                    $business_data,
                    $all_delete_ids
                );

                $this->recalculateSellTotals($transaction->fresh(['sell_lines']));
                $results['lines_deleted'] += count($line_ids);
            }

            DB::commit();
            $request->session()->forget($sessionKey);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('Shisha cleanup File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->action([self::class, 'index'])
                ->with('status', [
                    'success' => 0,
                    'msg' => 'فشل التنفيذ: '.$e->getMessage(),
                ]);
        }

        $msg = 'تم التنفيذ. فواتير محذوفة بالكامل: '.$results['invoices_deleted']
            .' | أسطر أصناف محذوفة: '.$results['lines_deleted'];
        if (! empty($results['errors'])) {
            $msg .= ' | تحذيرات: '.implode(' | ', $results['errors']);
        }

        return redirect()
            ->action([self::class, 'index'])
            ->with('status', [
                'success' => empty($results['errors']) ? 1 : 0,
                'msg' => $msg,
            ]);
    }

    protected function authorizeAccess()
    {
        if (! auth()->user()->can('sell.delete')) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function isZatcaLocked($transaction)
    {
        if (! $this->moduleUtil->isModuleInstalled('ZatcaIntegrationKsa')) {
            return false;
        }

        return ! empty($transaction->zatca_status) && $transaction->zatca_status === 'success';
    }

    protected function parseExcelRows($file)
    {
        $sheet = Excel::toArray([], $file);
        $rows_raw = $sheet[0] ?? [];
        $rows = [];
        $unique_products = [];

        foreach ($rows_raw as $index => $row) {
            if ($index === 0) {
                continue; // header
            }

            $product_name = trim((string) ($row[0] ?? ''));
            $invoice_no = trim((string) ($row[1] ?? ''));
            $date = trim((string) ($row[2] ?? ''));
            $qty_raw = trim((string) ($row[3] ?? ''));

            if ($product_name === '' || $invoice_no === '') {
                continue;
            }

            // skip total row
            if ($this->normalizeName($product_name) === $this->normalizeName('الاجمالي')
                || $this->normalizeName($product_name) === $this->normalizeName('الإجمالي')) {
                continue;
            }

            $qty = $this->parseQty($qty_raw);

            $rows[] = [
                'product_name' => $product_name,
                'invoice_no' => $invoice_no,
                'date' => $date,
                'quantity' => $qty,
                'excel_row' => $index + 1,
            ];
            $unique_products[$this->normalizeName($product_name)] = $product_name;
        }

        return [
            'rows' => $rows,
            'unique_products' => array_values($unique_products),
        ];
    }

    protected function parseQty($qty_raw)
    {
        if ($qty_raw === '') {
            return null;
        }
        // e.g. "1.00 حبة" or "2.00 حبة"
        if (preg_match('/([\d\.]+)/u', $qty_raw, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    protected function normalizeName($name)
    {
        $name = trim(mb_strtolower((string) $name, 'UTF-8'));
        // Use only the primary label before bilingual slash suffixes.
        if (str_contains($name, '/')) {
            $name = trim(explode('/', $name, 2)[0]);
        }
        $name = preg_replace('/\s+/u', ' ', $name);
        // Arabic letter variants
        $search = ['أ', 'إ', 'آ', 'ة', 'ى', 'ؤ', 'ئ', 'ٱ', 'نعناع'];
        $replace = ['ا', 'ا', 'ا', 'ه', 'ي', 'و', 'ي', 'ا', 'نعنع'];
        $name = str_replace($search, $replace, $name);

        return $name;
    }

    /**
     * Parse date values from Excel (serial number, Carbon instance, or string).
     */
    protected function parseExcelDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Exception $e) {
                // fall through to string parsing
            }
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $formats = [];
        $business_format = session('business.date_format');
        if (! empty($business_format)) {
            $formats[] = $business_format;
        }
        $formats = array_values(array_unique(array_merge($formats, [
            'm/d/Y', 'd/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d', 'd/m/Y H:i', 'm/d/Y H:i',
        ])));
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->startOfDay();
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Load sell transactions sharing an invoice number, narrowed by date/products.
     */
    protected function findTransactionsForGroup($business_id, $invoice_no, $date_raw, array $product_names = [])
    {
        $candidates = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('invoice_no', $invoice_no)
            ->with([
                'contact',
                'sell_lines' => function ($q) {
                    $q->whereNull('parent_sell_line_id')->with('product');
                },
            ])
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        if ($candidates->count() === 1) {
            return $candidates;
        }

        $all_candidates = $candidates;
        $parsed_date = $this->parseExcelDate($date_raw);
        if ($parsed_date) {
            $date_matched = $candidates->filter(function ($transaction) use ($parsed_date) {
                return Carbon::parse($transaction->transaction_date)->toDateString() === $parsed_date->toDateString();
            });

            if ($date_matched->isNotEmpty()) {
                $candidates = $date_matched->values();
            }
        }

        $targets = array_values(array_unique(array_filter(array_map(
            fn ($name) => $this->normalizeName($name),
            $product_names
        ))));

        if (! empty($targets)) {
            $with_products = $candidates->filter(function ($transaction) use ($targets) {
                $line_names = $transaction->sell_lines
                    ->map(fn ($line) => $this->normalizeName(optional($line->product)->name))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return count(array_intersect($targets, $line_names)) > 0;
            })->values();

            if ($with_products->isNotEmpty()) {
                return $with_products;
            }

            // One invoice on this date but product not in it — keep strict date match.
            if ($parsed_date && $candidates->count() === 1) {
                return $candidates;
            }

            // Same invoice number reused on another date — fall back to product match.
            $with_products = $all_candidates->filter(function ($transaction) use ($targets) {
                $line_names = $transaction->sell_lines
                    ->map(fn ($line) => $this->normalizeName(optional($line->product)->name))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return count(array_intersect($targets, $line_names)) > 0;
            })->values();

            if ($with_products->isNotEmpty()) {
                return $with_products;
            }
        }

        return $candidates;
    }

    /**
     * Match Excel rows to sell lines across one or more duplicate transactions.
     */
    protected function matchExcelRowsToTransactions($transactions, array $rows)
    {
        $used_line_ids = [];
        $matches = [];
        $unmatched = [];

        foreach ($rows as $row) {
            $target = $this->normalizeName($row['product_name']);
            $found_line = null;
            $found_transaction = null;

            foreach ($transactions as $transaction) {
                $tx_used = $used_line_ids[$transaction->id] ?? [];
                foreach ($transaction->sell_lines as $line) {
                    if (in_array($line->id, $tx_used, true)) {
                        continue;
                    }
                    if ($this->normalizeName(optional($line->product)->name) === $target) {
                        $found_line = $line;
                        $found_transaction = $transaction;
                        break 2;
                    }
                }
            }

            if ($found_line && $found_transaction) {
                $used_line_ids[$found_transaction->id][] = $found_line->id;
                $matches[] = [
                    'transaction' => $found_transaction,
                    'line' => $found_line,
                    'row' => $row,
                ];
            } else {
                $unmatched[] = array_merge($row, ['reason' => 'الصنف غير موجود في الفاتورة']);
            }
        }

        return [$matches, $unmatched];
    }

    protected function buildPreview($business_id, array $excel_rows)
    {
        $by_invoice = [];
        foreach ($excel_rows as $row) {
            $parsed_date = $this->parseExcelDate($row['date']);
            $date_key = $parsed_date ? $parsed_date->toDateString() : '';
            $group_key = $row['invoice_no'].'|'.$date_key;
            $by_invoice[$group_key][] = $row;
        }

        $invoices = [];
        $actions = [];
        $unmatched_rows = [];
        $blocked = [];
        $stats = [
            'invoices_found' => 0,
            'invoices_to_delete' => 0,
            'invoices_partial' => 0,
            'lines_to_delete' => 0,
            'invoices_missing' => 0,
        ];

        foreach ($by_invoice as $group_key => $rows) {
            $invoice_no = $rows[0]['invoice_no'];
            $product_names = array_column($rows, 'product_name');
            $transactions = $this->findTransactionsForGroup(
                $business_id,
                $invoice_no,
                $rows[0]['date'] ?? null,
                $product_names
            );

            if ($transactions->isEmpty()) {
                $stats['invoices_missing']++;
                $reason = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell')
                    ->where('invoice_no', $invoice_no)
                    ->exists()
                    ? 'عدة فواتير بنفس الرقم — تعذّر تحديد الفاتورة (تحقق من التاريخ)'
                    : 'الفاتورة غير موجودة';

                foreach ($rows as $r) {
                    $unmatched_rows[] = array_merge($r, ['reason' => $reason]);
                }
                continue;
            }

            [$row_matches, $invoice_unmatched] = $this->matchExcelRowsToTransactions($transactions, $rows);
            foreach ($invoice_unmatched as $u) {
                $unmatched_rows[] = $u;
            }

            $matches_by_transaction = [];
            foreach ($row_matches as $match) {
                $matches_by_transaction[$match['transaction']->id][] = $match;
            }

            $parsed_group_date = $this->parseExcelDate($rows[0]['date'] ?? null);

            foreach ($matches_by_transaction as $transaction_id => $tx_matches) {
                $transaction = $tx_matches[0]['transaction'];
                $matched_line_ids = array_values(array_unique(array_map(
                    fn ($match) => $match['line']->id,
                    $tx_matches
                )));

                $stats['invoices_found']++;

                $sell_lines = $transaction->sell_lines;
                $product_line_ids = $sell_lines->pluck('id')->all();
                $all_matched = ! empty($matched_line_ids)
                    && count($matched_line_ids) === count($product_line_ids);

                $zatca_locked = $this->isZatcaLocked($transaction);
                $has_return = $all_matched ? $this->transactionUtil->isReturnExist($transaction->id) : false;

                $can_execute = ! $zatca_locked && ! ($all_matched && $has_return);

                if ($zatca_locked || ($all_matched && $has_return)) {
                    $blocked[] = [
                        'invoice_no' => $invoice_no,
                        'reason' => $zatca_locked
                            ? 'مزامنة زاتكا ناجحة — لا يمكن التعديل/الحذف'
                            : 'يوجد مرتجع مرتبط بالفاتورة',
                    ];
                }

                $lines_view = [];
                foreach ($sell_lines as $line) {
                    $is_target = in_array($line->id, $matched_line_ids, true);
                    $lines_view[] = [
                        'id' => $line->id,
                        'product_name' => optional($line->product)->name,
                        'quantity' => (float) $line->quantity,
                        'unit_price' => (float) $line->unit_price,
                        'unit_price_inc_tax' => (float) $line->unit_price_inc_tax,
                        'line_total' => (float) $line->quantity * (float) $line->unit_price_inc_tax,
                        'is_target' => $is_target,
                    ];
                }

                if ($all_matched) {
                    $stats['invoices_to_delete']++;
                } elseif (! empty($matched_line_ids)) {
                    $stats['invoices_partial']++;
                }
                $stats['lines_to_delete'] += count($matched_line_ids);

                $matched_excel_rows = array_map(fn ($match) => $match['row'], $tx_matches);

                $invoices[] = [
                    'transaction_id' => $transaction->id,
                    'invoice_no' => $transaction->invoice_no,
                    'excel_date' => $parsed_group_date ? $parsed_group_date->format('Y-m-d') : null,
                    'transaction_date' => $transaction->transaction_date,
                    'customer' => optional($transaction->contact)->name,
                    'final_total' => (float) $transaction->final_total,
                    'status' => $transaction->status,
                    'delete_invoice' => $all_matched,
                    'matched_lines_count' => count($matched_line_ids),
                    'total_lines_count' => count($product_line_ids),
                    'lines' => $lines_view,
                    'excel_rows' => $matched_excel_rows,
                    'unmatched' => $invoice_unmatched,
                    'can_execute' => $can_execute && ! empty($matched_line_ids),
                    'blocked_reason' => $zatca_locked
                        ? 'زاتكا'
                        : ($has_return ? 'مرتجع' : null),
                ];

                if ($can_execute && ! empty($matched_line_ids)) {
                    $actions[] = [
                        'transaction_id' => $transaction->id,
                        'invoice_no' => $transaction->invoice_no,
                        'delete_invoice' => $all_matched,
                        'sell_line_ids' => $matched_line_ids,
                    ];
                }
            }
        }

        return compact('invoices', 'actions', 'unmatched_rows', 'blocked', 'stats');
    }

    /**
     * Recalculate sell totals after removing some lines.
     */
    protected function recalculateSellTotals(Transaction $transaction)
    {
        $lines = TransactionSellLine::where('transaction_id', $transaction->id)
            ->whereNull('parent_sell_line_id')
            ->get();

        if ($lines->isEmpty()) {
            // Should not happen if preview classified correctly; fallback delete.
            $this->transactionUtil->deleteSale($transaction->business_id, $transaction->id);

            return;
        }

        $total_before_tax = 0;
        $tax_amount = 0;
        foreach ($lines as $line) {
            $total_before_tax += ((float) $line->unit_price * (float) $line->quantity);
            $tax_amount += ((float) $line->item_tax * (float) $line->quantity);
        }

        $discount = 0;
        if ($transaction->discount_type == 'percentage') {
            $discount = ($transaction->discount_amount / 100) * $total_before_tax;
        } else {
            $discount = (float) ($transaction->discount_amount ?? 0);
        }

        $shipping = (float) ($transaction->shipping_charges ?? 0);
        $packing = (float) ($transaction->packing_charge ?? 0);
        $round_off = (float) ($transaction->round_off_amount ?? 0);
        $additional = (float) ($transaction->additional_expense_value_1 ?? 0)
            + (float) ($transaction->additional_expense_value_2 ?? 0)
            + (float) ($transaction->additional_expense_value_3 ?? 0)
            + (float) ($transaction->additional_expense_value_4 ?? 0);

        $final_total = $total_before_tax + $tax_amount - $discount + $shipping + $packing + $additional + $round_off;

        $transaction->total_before_tax = $total_before_tax;
        $transaction->tax_amount = $tax_amount;
        $transaction->final_total = $final_total;
        $transaction->save();

        $this->transactionUtil->updatePaymentStatus($transaction->id, $final_total);

        $this->transactionUtil->activityLog($transaction, 'edited', null, [
            'note' => 'shisha_cleanup_partial_line_removal',
        ]);
    }
}
