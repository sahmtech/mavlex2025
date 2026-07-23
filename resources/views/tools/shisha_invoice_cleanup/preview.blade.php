@extends('layouts.app')
@section('title', 'معاينة تنظيف فواتير الشيشة')

@section('content')
<style>
    .shisha-target-line {
        background-color: #ffe5e5 !important;
        color: #b10000 !important;
        font-weight: 600;
    }
    .shisha-invoice-delete {
        border: 2px solid #c62828;
        background: #fff5f5;
    }
    .shisha-badge-delete {
        background: #c62828;
        color: #fff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    .shisha-badge-partial {
        background: #ef6c00;
        color: #fff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
</style>

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">معاينة قبل الحذف</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['title' => 'ملخص'])
                <div class="row text-center">
                    <div class="col-sm-2"><b>{{ $excel_rows_count }}</b><br><small>صفوف الإكسل</small></div>
                    <div class="col-sm-2"><b>{{ $stats['invoices_found'] }}</b><br><small>فواتير وُجدت</small></div>
                    <div class="col-sm-2"><b>{{ $stats['invoices_to_delete'] }}</b><br><small>فواتير للحذف كامل</small></div>
                    <div class="col-sm-2"><b>{{ $stats['invoices_partial'] }}</b><br><small>فواتير حذف جزئي</small></div>
                    <div class="col-sm-2"><b>{{ $stats['lines_to_delete'] }}</b><br><small>أسطر ستُحذف</small></div>
                    <div class="col-sm-2"><b>{{ $stats['invoices_missing'] }}</b><br><small>فواتير غير موجودة</small></div>
                </div>
                @if(!empty($unique_products))
                    <hr>
                    <strong>الأصناف من الملف:</strong>
                    <div style="margin-top:8px;">
                        @foreach($unique_products as $p)
                            <span class="label label-default" style="display:inline-block;margin:2px;background:#eee;color:#333;padding:4px 8px;">{{ $p }}</span>
                        @endforeach
                    </div>
                @endif
            @endcomponent
        </div>
    </div>

    @if(!empty($blocked))
        <div class="alert alert-warning">
            <b>فواتير محظورة من التنفيذ:</b>
            <ul style="margin-top:8px;">
                @foreach($blocked as $b)
                    <li>{{ $b['invoice_no'] }} — {{ $b['reason'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($unmatched_rows))
        <div class="alert alert-danger">
            <b>صفوف لم تُطابق:</b>
            <ul style="margin-top:8px; max-height:180px; overflow:auto;">
                @foreach($unmatched_rows as $u)
                    <li>
                        صف {{ $u['excel_row'] ?? '-' }} |
                        فاتورة: {{ $u['invoice_no'] }} |
                        صنف: {{ $u['product_name'] }} |
                        السبب: {{ $u['reason'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse($invoices as $invoice)
        <div class="box {{ $invoice['delete_invoice'] ? 'shisha-invoice-delete' : '' }}">
            <div class="box-header with-border">
                <h3 class="box-title" style="width:100%;">
                    فاتورة
                    <b>{{ $invoice['invoice_no'] }}</b>
                    @if(!empty($invoice['excel_date']))
                        <small class="text-muted">(تاريخ الإكسل: {{ $invoice['excel_date'] }})</small>
                    @endif
                    —
                    {{ $invoice['customer'] }}
                    —
                    {{ @format_datetime($invoice['transaction_date']) }}
                    —
                    الإجمالي الحالي: <b>@format_currency($invoice['final_total'])</b>

                    @if($invoice['delete_invoice'])
                        <span class="shisha-badge-delete pull-left">حذف الفاتورة كاملة</span>
                    @elseif($invoice['matched_lines_count'] > 0)
                        <span class="shisha-badge-partial pull-left">
                            حذف جزئي ({{ $invoice['matched_lines_count'] }}/{{ $invoice['total_lines_count'] }} أسطر)
                        </span>
                    @endif

                    @if(!$invoice['can_execute'])
                        <span class="label label-warning pull-left" style="margin-left:6px;">لن تُنفَّذ</span>
                    @endif
                </h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصنف</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>شامل الضريبة</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice['lines'] as $i => $line)
                            <tr class="{{ !empty($line['is_target']) ? 'shisha-target-line' : '' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $line['product_name'] }}</td>
                                <td>{{ @format_quantity($line['quantity']) }}</td>
                                <td>@format_currency($line['unit_price'])</td>
                                <td>@format_currency($line['unit_price_inc_tax'])</td>
                                <td>@format_currency($line['line_total'])</td>
                                <td>
                                    @if(!empty($line['is_target']))
                                        <b style="color:#b10000;">سيُحذف</b>
                                    @else
                                        يبقى
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if(!empty($invoice['unmatched']))
                    <div class="alert alert-warning" style="margin:10px;">
                        صفوف إكسل لهذه الفاتورة بلا مطابقة:
                        <ul>
                            @foreach($invoice['unmatched'] as $u)
                                <li>{{ $u['product_name'] }} — {{ $u['reason'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">لا توجد فواتير مطابقة للمعاينة.</div>
    @endforelse

    <div class="box box-danger">
        <div class="box-header with-border">
            <h3 class="box-title">تأكيد التنفيذ</h3>
        </div>
        <div class="box-body">
            <p>
                سيتم حذف الأسطر المميزة بالأحمر. والفواتير المعلّمة
                <span class="shisha-badge-delete">حذف الفاتورة كاملة</span>
                ستُحذف بالكامل مع إرجاع المخزون.
            </p>
            {!! Form::open([
                'url' => action([\App\Http\Controllers\ShishaInvoiceCleanupController::class, 'confirm']),
                'method' => 'post',
                'id' => 'shisha_cleanup_confirm_form',
            ]) !!}
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="form-group" style="max-width:360px;">
                    <label>اكتب <b>تأكيد</b> أو <b>CONFIRM</b> للمتابعة:</label>
                    <input type="text" name="confirm_text" class="form-control" required autocomplete="off">
                </div>
                <a href="{{ action([\App\Http\Controllers\ShishaInvoiceCleanupController::class, 'index']) }}" class="tw-dw-btn tw-dw-btn-neutral">
                    رجوع بدون حذف
                </a>
                <button type="submit" class="tw-dw-btn tw-dw-btn-error tw-text-white"
                    onclick="return confirm('تأكيد نهائي: هل تريد تنفيذ الحذف الآن؟');">
                    تنفيذ الحذف بعد المعاينة
                </button>
            {!! Form::close() !!}
        </div>
    </div>
</section>
@endsection
