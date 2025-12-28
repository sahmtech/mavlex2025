@extends('layouts.app')

@section('title', __('accounting::lang.payment_vouchers'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.payment_vouchers')</h1>
    </section>
    <section class="content no-print">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    @include('accounting::payment_vouchers.payment_list_filters')
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('payment_voucher_date_range_filter', __('report.date_range') . ':') !!}
                            {!! Form::text('payment_voucher_date_range_filter', null, [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                            ]) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        @component('components.widget', ['class' => 'box-solid'])
            @if (auth()->user()->can('Admin#'.request()->session()->get('user.business_id')) ||auth()->user()->can('superadmin') ||
                    auth()->user()->can('accounting.add_payment_vouchers'))
                @slot('tool')
                    <div class="box-tools">
                        <a class="btn btn-block btn-primary btn-modal create_voucher" data-toggle="modal"
                            data-target="#create_payment_voucher_modal">
                            <i class="fas fa-plus"></i> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endif

            <table class="table table-bordered table-striped" id="payment_voucher_table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.receipt_voucher_no')</th>
                        <th>@lang('accounting::lang.receipt_voucher_ent_date')</th>
                        <th>@lang('accounting::lang.receipt_voucher_date')</th>
                        <th>@lang('accounting::lang.invoice_no')</th>
                        <th>@lang('accounting::lang.supplier')</th>
                        <th>@lang('accounting::lang.account_no')</th>
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
    @include('accounting::payment_vouchers.create')
@stop

@section('javascript')
    <script>
        $(document).ready(function() {
            $('#payment_voucher_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    // url: '/accounting/payment_vouchers',
                    url: '{{ route('index-payment_vouchers') }}',
                    "data": function(d) {
                        console.log(d);
                        if ($('#payment_voucher_date_range_filter').val()) {
                            var start = $('#payment_voucher_date_range_filter').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#payment_voucher_date_range_filter').data('daterangepicker')
                                .endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.customer_id = $('#payment_voucher_filter_customer_id').val();
                        d.account_id = $('#payment_voucher_filter_account_id').val();
                        d.payment_status = $('#payment_voucher_filter_payment_status').val();
                    }

                },
                aaSorting: [
                    [1, 'desc']
                ],
                columns: [{
                        "data": "payment_ref_no"
                    },
                    {
                        "data": "paid_on"
                    },
                    {
                        "data": "created_at"
                    },
                    {
                        "data": "voucher_number"
                    },
                    {
                        "data": "contact_id"
                    },
                    {
                        "data": "account_id"
                    },
                    {
                        "data": "amount"
                    },
                    {
                        "data": "note"
                    },
                    {
                        "data": "method"
                    },
                    {
                        "data": "action"
                    }
                ]

            })

        });

        $('#payment_voucher_date_range_filter').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#payment_voucher_date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(
                    moment_date_format));
                $('#payment_voucher_table').DataTable().ajax.reload();
            }
        );
        $('#payment_voucher_date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#payment_voucher_date_range_filter').val('');
            $('#payment_voucher_table').DataTable().ajax.reload();
        });
        $(document).on('change',
            '#payment_voucher_filter_customer_id, #payment_voucher_filter_account_id, #payment_voucher_filter_payment_status',
            function() {
                $('#payment_voucher_table').DataTable().ajax.reload();
            });
    </script>
    <script>
        $(document).ready(function() {
            $(this).find('#contact_id').select2({
                tags: true,
                renderTemplate: item => item.text || '\u200B',
                dropdownParent: $("#create_payment_voucher_modal"),
                minimumResultsForSearch: 1
            });
            $(this).find('#transaction_id').select2({
                tags: true,
                renderTemplate: item => item.text || '\u200B',
                dropdownParent: $("#create_payment_voucher_modal"),
                minimumResultsForSearch: 1
            });
            $(this).find('#account_id').select2({
                tags: true,
                renderTemplate: item => item.text || '\u200B',
                dropdownParent: $("#create_payment_voucher_modal"),
                minimumResultsForSearch: 1
            });
        });

        $(document).on('click', '.create_voucher', function() {
            $('#create_payment_voucher_form').trigger('reset')
            // $('#transaction_id_label').hide()
            $('#transaction_id').prop('disabled', true)
            $('#transaction_id').prop('required', false)
            $('#account_id').prop('disabled', true)
            $('#account_id').prop('required', false)
            $('#method option').each(function() {
                if ($(this).val() == 'advance') {
                    $(this).remove().trigger('change');
                }
            });

        }) // done


        $('input[name=payment_voucher_type]').change(function() {
            if ($(this).val() === 'supplier') {
                $('#account_id').prop('disabled', true)
                $('#account_id').prop('required', false)
                $("#account_id").val('').change()
                $('#contact_id').prop('disabled', false)
                $('#contact_id').prop('required', true)

                $('#payment_type_voucher').prop('disabled', false)

            } else {
                $('#account_id').prop('disabled', false)
                $('#account_id').prop('required', true)
                $('#contact_id').prop('disabled', true)
                $('#contact_id').prop('required', false)
                $("#contact_id").val('').change()

                $('#payment_type_voucher').prop('disabled', true)
                $('#payment_type_voucher').prop('checked', false)
                $('#payment_type_value').prop('checked', true)

                $('#transaction_id').prop('disabled', 'true')
                $('#transaction_id').prop('required', false)
                $("#transaction_id").val('').change()

                $('#method option').each(function() {
                    if ($(this).val() == 'advance') {
                        $(this).remove().trigger('change');
                    }
                });


            }
        })
        $('input[name=payment_type]').change(function() {
            if ($(this).val() === 'value') {
                $('#transaction_id').prop('disabled', 'true')
                $('#transaction_id').prop('required', false)
                $("#transaction_id").val('').change()

                $('#method option').each(function() {
                    if ($(this).val() == 'advance') {
                        $(this).remove().trigger('change');
                    }
                });
            } else {
                $('#transaction_id').prop('disabled', false)
                $('#transaction_id').prop('required', true)
                let data = {
                    val: 'advance',
                    text: 'الدفع المسبق'
                };
                let newOption = new Option(data.text, data.val, false, false);
                $('#method').prepend(newOption).trigger('change');
                $('#method').val('advance').trigger('change')
            }
        }); // done

        $(document).on('change', '#contact_id', function() {
            let contact_id = $('#contact_id').val()
            if (contact_id) {
                let url = "{{ route('payment_vouchers.load') }}"
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
                                    text: propertyValues[i].type + ' - ' + propertyValues[i].ref_no
                                };
                                let newOption = new Option(data.text, data.id, false, false);
                                $('#transaction_id').append(newOption).trigger('change');
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                })
            }
        }) // done


        $(document).on('change', '#transaction_id', function() {
            let transaction_id = $('#transaction_id').val()
            if (transaction_id) {
                let url = "{{ route('payment_vouchers.load') }}"
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


        }) // done
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
