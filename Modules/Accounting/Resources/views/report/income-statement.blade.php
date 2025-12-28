@extends('layouts.app')

@section('title', __('accounting::lang.income_list'))
@section('css')
    <style>
        .bg-info {
            background-color: #00c0ef14 !important;
        }
    </style>
@stop
@section('content')

    <section class="content-header">
        <h1>@lang('accounting::lang.income_list')</h1>
    </section>

    <section class="content container">

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('date_range_filter', __('report.date_range') . ':') !!}
                {!! Form::text('date_range_filter', null, [
                    'placeholder' => __('lang_v1.select_a_date_range'),
                    'class' => 'form-control',
                    'readonly',
                    'id' => 'date_range_filter',
                ]) !!}
            </div>
        </div>

        <div class="col-md-12">

            <div class="box box-default">
                <div class="box-header with-border text-center">
                    <h2 class="box-title">@lang('accounting::lang.income_list')</h2>
                    <p>{{ @format_date($start_date) }} ~ {{ @format_date($end_date) }}</p>
                </div>

                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="accounts-table">
                            <thead>
                                <tr class="bg-primary">
                                    <th>@lang('accounting::lang.account_name')</th>
                                    <th>@lang('accounting::lang.amount')</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Revenues Section -->
                                <tr class="bg-info">
                                    <td colspan="2">
                                        <h4>@lang('accounting::lang.Revenues')</h4>
                                    </td>
                                </tr>
                                @foreach ($accounts as $account)
                                    @if (str_starts_with($account->gl_code, '3'))
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td>@format_currency(abs($account->credit_balance - $account->debit_balance))</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr class="bg-light">
                                    <td><strong>@lang('accounting::lang.total_revenues')</strong></td>
                                    <td><strong>@format_currency($data->revenue_net)</strong></td>
                                </tr>

                                <!-- Cost of Goods Sold Section -->
                                <tr class="bg-info">
                                    <td colspan="2">
                                        <h4>@lang('accounting::lang.cost_goods_sold')</h4>
                                    </td>
                                </tr>
                                @foreach ($accounts as $account)
                                    @if (str_starts_with($account->gl_code, '51'))
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td>@format_currency(abs($account->debit_balance - $account->credit_balance))</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr class="bg-light">
                                    <td><strong>@lang('accounting::lang.total_cost_goods_sold')</strong></td>
                                    <td><strong>@format_currency($data->cost_of_revenue)</strong></td>
                                </tr>

                                <!-- Gross Profit -->
                                <tr class="bg-success">
                                    <td><strong>@lang('accounting::lang.gross_profit')</strong></td>
                                    <td><strong>@format_currency($data->gross_profit)</strong></td>
                                </tr>

                                <!-- Operating Expenses Section -->
                                <tr class="bg-info">
                                    <td colspan="2">
                                        <h4>@lang('accounting::lang.operating_expense')</h4>
                                    </td>
                                </tr>
                                @foreach ($accounts as $account)
                                    @if (str_starts_with($account->gl_code, '21'))
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td>@format_currency(abs($account->debit_balance - $account->credit_balance))</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr class="bg-light">
                                    <td><strong>@lang('accounting::lang.total_operating_expense')</strong></td>
                                    <td><strong>@format_currency($data->total_expense)</strong></td>
                                </tr>

                                <!-- Income from Operation -->
                                <tr class="bg-success">
                                    <td><strong>@lang('accounting::lang.income_from_operation')</strong></td>
                                    <td><strong>@format_currency($data->operation_income)</strong></td>
                                </tr>

                                <!-- Other Revenues and Expenses Section -->
                                <tr class="bg-info">
                                    <td colspan="2">
                                        <h4>@lang('accounting::lang.other_revenues')</h4>
                                    </td>
                                </tr>
                                @foreach ($accounts as $account)
                                    @if (str_starts_with($account->gl_code, '22'))
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td>@format_currency(abs($account->credit_balance - $account->debit_balance))</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr class="bg-light">
                                    <td><strong>@lang('accounting::lang.total_other_revenues')</strong></td>
                                    <td><strong>@format_currency($data->total_other_income)</strong></td>
                                </tr>

                                <tr class="bg-info">
                                    <td colspan="2">
                                        <h4>@lang('accounting::lang.other_expenses')</h4>
                                    </td>
                                </tr>
                                @foreach ($accounts as $account)
                                    @if (str_starts_with($account->gl_code, '23'))
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td>@format_currency(abs($account->debit_balance - $account->credit_balance))</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr class="bg-light">
                                    <td><strong>@lang('accounting::lang.total_other_expenses')</strong></td>
                                    <td><strong>@format_currency($data->total_other_expense)</strong></td>
                                </tr>

                                <!-- Income Before Tax and Final Total -->
                                <tr class="bg-success">
                                    <td><strong>@lang('accounting::lang.income_before_tax')</strong></td>
                                    <td><strong>@format_currency($data->income_before_tax)</strong></td>
                                </tr>
                                <tr class="bg-light">
                                    <td><strong>@lang('accounting::lang.autoMigration.tax_amount')</strong></td>
                                    <td><strong>@format_currency($data->tax_amount)</strong></td>
                                </tr>
                                <tr class="bg-success">
                                    <td><strong>@lang('accounting::lang.autoMigration.final_total')</strong></td>
                                    <td><strong>@format_currency($data->income_before_tax - $data->tax_amount)</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            dateRangeSettings.startDate = moment('{{ $start_date }}');
            dateRangeSettings.endDate = moment('{{ $end_date }}');

            $('#date_range_filter').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    apply_filter();
                }
            );
            $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
                $('#date_range_filter').val('');
                apply_filter();
            });

            function apply_filter() {
                var start = '';
                var end = '';

                if ($('#date_range_filter').val()) {
                    start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                }

                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('start_date', start);
                urlParams.set('end_date', end);
                window.location.search = urlParams;
            }

            $('#accounts-table').DataTable({
                "aaSorting": [],
                pageLength: 100
            });
        });
    </script>
@stop
