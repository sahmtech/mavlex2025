@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))
@section('content')
    @include('zatcaintegrationksa::layouts.nav')
    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.sell_return')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('zatca_status', __('zatcaintegrationksa::lang.zatca_status') . ':') !!}
                    {!! Form::select(
                        'zatca_status',
                        [
                            'pending' => __('zatcaintegrationksa::lang.pending'),
                            'success' => __('zatcaintegrationksa::lang.success'),
                            'failed' => __('zatcaintegrationksa::lang.failed'),
                        ],
                        null,
                        ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')],
                    ) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}
                    {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.sell_return')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="sell_return_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('zatcaintegrationksa::lang.zatca_status')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('lang_v1.parent_sale')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('purchase.payment_status')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('purchase.payment_due')</th>
                            
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_payment_status_count_sr"></td>
                            <td><span class="display_currency" id="footer_sell_return_total"
                                    data-currency_symbol ="true"></span>
                            </td>
                            <td><span class="display_currency" id="footer_total_due_sr" data-currency_symbol ="true"></span>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endcomponent
        <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>
    </section>
@endsection
@section('javascript')
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        $(document).ready(function() {
            $('#sell_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    sell_return_table.ajax.reload();
                }
            );
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#sell_list_filter_date_range').val('');
                sell_return_table.ajax.reload();
            });

            sell_return_table = $('#sell_return_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                aaSorting: [
                    [0, 'desc']
                ],
                "ajax": {
                    "url": "/sell-return",
                    "data": function(d) {
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.is_zatca = 1;
                        if ($('#zatca_status').length) {
                            d.zatca_status = $('#zatca_status').val();
                        }
                    }
                },
                columnDefs: [{
                    "targets": [7, 8],
                    "orderable": false,
                    "searchable": false
                }],
                columns: [
                    {
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'zatca_status',
                        name: 'zatca_status'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'parent_sale',
                        name: 'T1.invoice_no'
                    },
                    {
                        data: 'name',
                        name: 'contacts.name'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'payment_due',
                        name: 'payment_due'
                    }
                   
                ],
                "fnDrawCallback": function(oSettings) {
                    var total_sell = sum_table_col($('#sell_return_table'), 'final_total');
                    $('#footer_sell_return_total').text(total_sell);

                    $('#footer_payment_status_count_sr').html(__sum_status_html($('#sell_return_table'),
                        'payment-status-label'));

                    var total_due = sum_table_col($('#sell_return_table'), 'payment_due');
                    $('#footer_total_due_sr').text(total_due);

                    __currency_convert_recursively($('#sell_return_table'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(2)').attr('class', 'clickable_td');
                }
            });

            $(document).on('change',
                '#zatca_status, #sell_list_filter_location_id',
                function() {
                    sell_return_table.ajax.reload();
                });
        })
        $(document).on('click', '.return_sale_sycs', function(e) {
            e.preventDefault(); // Stop the page from loading
            var href = $(this).attr('href');
            var button = $(this);
            $.ajax({
                method: 'GET',
                url: href,
                dataType: 'json',
                beforeSend: function() {
                    button.html(
                        '<span class="spinner-border spinner-border-sm"></span> Syncing...'
                    ); // Add syncing indicator before the AJAX call
                },
                success: function(result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        sell_return_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                        sell_return_table.ajax.reload();
                    }
                },
                complete: function() {
                    button.html('Sync'); // Reset button text after syncing
                }
            });
        });
        $(document).on("click", ".download-xml", function(e) {
            e.preventDefault(); // Prevent default link behavior

            let url = $(this).attr("href");
            let link = $("<a>").attr("href", url).appendTo("body");
            link[0].click();
            link.remove();
        });
        $(document).on('click', '.status_fail', function(e) {
            e.preventDefault(); // Stop the page from loading
            var href = $(this).attr('href');
            var button = $(this); // Store the button element for later use
            $.ajax({
                method: 'GET',
                url: href,
                dataType: 'html', // Changed dataType to 'html'
                success: function(result) {
                    $('.show_error').html(result)
                        .modal('show');
                },
            });
        });
    </script>

@endsection
