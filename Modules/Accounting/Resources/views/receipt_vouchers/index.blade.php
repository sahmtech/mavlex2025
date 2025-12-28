@extends('layouts.app')

@section('title', __('accounting::lang.receipt_vouchers'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.receipt_vouchers')</h1>
    </section>
    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    @include('accounting::receipt_vouchers.receipt_list_filters')
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('receipt_voucher_date_range_filter', __('report.date_range') . ':') !!}
                            {!! Form::text('receipt_voucher_date_range_filter', null, [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                            ]) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        @component('components.widget', ['class' => 'box-solid'])
            @if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ||
                    auth()->user()->can('superadmin') ||
                    auth()->user()->can('accounting.add_receipt_vouchers'))
                @slot('tool')
                    <div class="box-tools">
                        <a class="btn btn-block btn-primary btn-modal create_voucher" data-toggle="modal"
                            data-target="#create_receipt_voucher_modal">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endif

            <table class="table table-bordered table-striped" id="receipt_voucher_table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.receipt_voucher_no')</th>
                        <th>@lang('accounting::lang.receipt_voucher_ent_date')</th>
                        <th>@lang('accounting::lang.receipt_voucher_date')</th>
                        <th>@lang('accounting::lang.invoice_no')</th>
                        <th>@lang('accounting::lang.customer')</th>
                        <th>@lang('accounting::lang.amount')</th>
                        <th>@lang('accounting::lang.note')</th>
                        <th>@lang('accounting::lang.payment_method')</th>
                        <th>@lang('accounting::lang.print')</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        @endcomponent
    </section>
    {{--    @include('accounting::cost_center.edit') --}}
    @include('accounting::receipt_vouchers.create')
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            $('#receipt_voucher_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/accounting/receipt_vouchers',
                    "data": function(d) {
                        if ($('#receipt_voucher_date_range_filter').val()) {
                            var start = $('#receipt_voucher_date_range_filter').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#receipt_voucher_date_range_filter').data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.customer_id = $('#receipt_voucher_filter_customer_id').val();
                        d.payment_status = $('#receipt_voucher_filter_payment_status').val();
                    }

                },
                aaSorting: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'payment_ref_no',
                        name: 'payment_ref_no'
                    },
                    {
                        data: 'paid_on',
                        name: 'paid_on'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'voucher_number',
                        name: 'voucher_number'
                    },
                    {
                        data: 'contact_id',
                        name: 'contact_id'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'method',
                        name: 'method'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    }
                ]
            })

        });

        $('#receipt_voucher_date_range_filter').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#receipt_voucher_date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(
                    moment_date_format));
                $('#receipt_voucher_table').DataTable().ajax.reload();
            }
        );
        $('#receipt_voucher_date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#receipt_voucher_date_range_filter').val('');
            $('#receipt_voucher_table').DataTable().ajax.reload();
        });
        $(document).on('change', '#receipt_voucher_filter_customer_id, #receipt_voucher_filter_payment_status', function() {
            $('#receipt_voucher_table').DataTable().ajax.reload();
        });
    </script>
    <script>
        $(document).ready(function() {
            $(this).find('#contact_id').select2({
                tags: true,
                renderTemplate: item => item.text || '\u200B',
                dropdownParent: $("#create_receipt_voucher_modal"),
                minimumResultsForSearch: 1
            });
            $(this).find('#transaction_id').select2({
                tags: true,
                renderTemplate: item => item.text || '\u200B',
                dropdownParent: $("#create_receipt_voucher_modal"),
                minimumResultsForSearch: 1
            });
        });

        $(document).on('click', '.create_voucher', function() {
            $('#create_receipt_voucher_form').trigger('reset')
            // $('#transaction_id_label').hide()
            $('#transaction_id').prop('disabled', true)
            $('#transaction_id').prop('required', false)
        })
        $('input[name=receipt_type]').change(function() {
            if ($(this).val() === 'value') {
                $('#transaction_id').prop('disabled', 'true')
                $('#transaction_id').prop('required', false)
                $("#transaction_id").val('').change()
            } else {
                $('#transaction_id').prop('disabled', false)
                $('#transaction_id').prop('required', true)
            }
        });

        $(document).on('change', '#contact_id', function() {
            let contact_id = $('#contact_id').val()
            let url = "{{ route('receipt_vouchers.load') }}"
            $('#transaction_id option').remove();
            $('#transaction_id').append('<option value="">@lang('messages.please_select')</option>').trigger('change');
            $.ajax({
                "method": 'GET',
                'url': url,
                'data': {
                    'contact_id': contact_id
                },
                success: function(result) {
                    if (result.success == true) {
                        const propertyValues = Object.values(result.trans);
                        for (let i = 0; i < propertyValues.length; i++) {
                            let data = {
                                id: propertyValues[i].id,
                                text: propertyValues[i].type + ' - ' + propertyValues[i].invoice_no
                            };
                            let newOption = new Option(data.text, data.id, false, false);
                            $('#transaction_id').append(newOption).trigger('change');
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                }
            })
        })
        $(document).on('change', '#transaction_id', function() {
            let transaction_id = $('#transaction_id').val()
            if (transaction_id) {
                let url = "{{ route('receipt_vouchers.load') }}"
                $.ajax({
                    "method": 'GET',
                    'url': url,
                    'data': {
                        'transaction_id': transaction_id
                    },
                    success: function(result) {
                        if (result.success == true) {
                            $('#amount').val(result.amount)
                            $('#amount').prop('max', Number(result.amount))
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                })
            }


        })

        $(document).on('click', 'button.delete_cost_center_button', function() {
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_cost_center,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    let href = $(this).data('href');
                    let data = $(this).serialize();

                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                $('#cost_center_table').DataTable().ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
