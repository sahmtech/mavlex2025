@extends('layouts.app')

@section('title', __('accounting::lang.automatedMigration'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('accounting::lang.automatedMigration')</h1>
    </section>

    <section class="content">
        @if (!$mappingSettings->isEmpty())
            <div class="row">
                <div class="col-md-12">
                    @component('components.filters', ['title' => __('report.filters'), 'class' => 'box-solid'])
                        <div class="row">
                            <div class="col-sm-4">
                                {!! Form::label('locations', __('accounting::lang.autoMigration.business_location')) !!}

                                <select class="form-control" name="location_id" id='location_id' style="padding: 2px;">
                                    <option value="all" selected>@lang('lang_v1.all')</option>
                                    @foreach ($business_locations as $locations)
                                        <option value="{{ $locations->id }}">
                                            {{ $locations->name }}</option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="col-sm-4">
                                {!! Form::label('type_fillter_lable', __('accounting::lang.operatio_type')) !!}
                                <select class="form-control" name="type_fillter" id="type_fillter"style="padding: 3px" required>
                                    <option value="all" selected>@lang('lang_v1.all')</option>
                                    <option value="sell">@lang('accounting::lang.autoMigration.sell')</option>
                                    <option value="sell_return">@lang('accounting::lang.autoMigration.sell_return')</option>
                                    <option value="opening_stock">@lang('accounting::lang.autoMigration.opening_stock')</option>
                                    <option value="purchase">@lang('accounting::lang.autoMigration.purchase')</option>
                                    <option value="purchase_order">@lang('accounting::lang.autoMigration.purchase_order')</option>
                                    <option value="purchase_return">@lang('accounting::lang.autoMigration.purchase_return')</option>
                                    <option value="expense">@lang('accounting::lang.autoMigration.expense')</option>
                                    <option value="sell_transfer">@lang('accounting::lang.autoMigration.sell_transfer')</option>
                                    <option value="purchase_transfer">@lang('accounting::lang.autoMigration.purchase_transfer')</option>
                                    <option value="payroll">@lang('accounting::lang.autoMigration.payroll')</option>
                                    <option value="opening_balance">@lang('accounting::lang.autoMigration.opening_balance')</option>
                                </select>


                            </div>

                            <div class="col-sm-4">
                                {!! Form::label('mappingSetting_fillter', __('accounting::lang.migration_name')) !!}

                                <select class="form-control" name="mappingSetting_fillter" id='mappingSetting_fillter'
                                    style="padding: 2px;">
                                    <option value="all" selected>@lang('lang_v1.all')</option>
                                    @foreach ($mappingSetting_fillter as $mappingSetting)
                                        <option value="{{ $mappingSetting->name }}">
                                            @lang('accounting::lang.' . $mappingSetting->name) </option>
                                    @endforeach
                                </select>

                            </div>


                        </div>
                    @endcomponent
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-solid'])
                    @if ($mappingSettings->isEmpty())
                        <div style="text-align: center; ">
                            <h3>@lang('accounting::lang.no_auto_migration')</h3>
                            <p>@lang('accounting::lang.add_auto_migration_help')</p>
                            <a href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@create_deflute_auto_migration') }}"
                                data-href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@create_deflute_auto_migration') }}"
                                data-container="#create_defulat_account_modal" class="btn btn-success btn-xs btn-modal">
                                <i class="fas fa-plus"></i> @lang('accounting::lang.add_new_auto_migration')
                            </a>
                        </div>
                    @else
                        @slot('tool')
                            <div class="box-tools">
                                <a class="btn btn-primary pull-right m-5 btn-modal"
                                    href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@create_deflute_auto_migration') }}"
                                    data-href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@create_deflute_auto_migration') }}"
                                    data-container="#create_account_modal">
                                    <i class="fas fa-plus"></i> @lang('messages.add')</a>
                            </div>
                        @endslot

                        <div class="col-sm-12">
                            <h4 style="text-align: start">@lang('accounting::lang.migration_list')</h4>


                            <table class="table table-bordered table-striped hide-footer" id="auto_migration_table">
                                <thead>
                                    <tr>
                                        <th class="col-md-1">#
                                        </th>
                                        <th class="col-md-3">@lang('accounting::lang.migration_name')</th>
                                        <th class="col-sm-3">@lang('accounting::lang.operatio_type')</th>
                                        <th class="col-sm-3" style="width: 12%;">@lang('accounting::lang.payment_stauts')</th>
                                        <th class="col-sm-2">@lang('accounting::lang.payment_method')</th>
                                        <th class="col-sm-3">@lang('accounting::lang.autoMigration.business_location')</th>
                                        <th class="col-md-2">@lang('accounting::lang.migratio_status')</th>
                                    </tr>
                                </thead>



                            </table>
                    @endif
                </div>
            @endcomponent
        </div>
        </div>
    </section>

    <div class="modal fade" id="create_account_modal" tabindex="-1" role="dialog"></div>
    <div class="modal fade" id="create_defulat_account_modal" tabindex="-1" role="dialog"></div>
    <div class="modal fade" id="delete_auto_migration" tabindex="-1" role="dialog">
        @include('accounting::AutomatedMigration.deleteDialog')
    </div>
@stop


@section('javascript')
    @include('accounting::accounting.common_js')

    <script type="text/javascript">
        $(document).ready(function() {

            auto_migration_table = $('#auto_migration_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('automated-migration.index') }}',
                    data: function(d) {
                        if ($('#mappingSetting_fillter').val()) {
                            d.mappingSetting_fillter = $('#mappingSetting_fillter').val();

                        }
                        if ($('#location_id').val()) {
                            d.location_id = $('#location_id').val();

                        }
                        if ($('#type_fillter').val()) {
                            d.type_fillter = $('#type_fillter').val();

                        }
                    }
                },

                columns: [{
                        "data": "action"
                    },
                    {
                        "data": "name"
                    },
                    {
                        "data": "type"
                    },
                    {
                        "data": "payment_status"
                    },
                    {
                        "data": "method"
                    },
                    {
                        "data": "businessLocation_name"
                    },
                    {
                        "data": "active"
                    }
                ]
            });


            $('#mappingSetting_fillter,#location_id,#type_fillter').on('change',
                function() {
                    auto_migration_table.ajax.reload();
                });
            $('#auto_migration_table tbody').append($(
                "<tr><td class=\"containter\"></td>td class=\"containter\"></td>td class=\"containter\"></td></tr>"
            ));
            $('.journal_add_btn').click(function(e) {
                //e.preventDefault();
                calculate_total();

                var is_valid = true;

                //check if same or not
                if ($('.total_credit_hidden').val() != $('.total_debit_hidden').val()) {
                    is_valid = false;
                    alert("@lang('accounting::lang.credit_debit_equal')");
                }

                //check if all account selected or not
                $('table > tbody  > tr').each(function(index, tr) {
                    var credit = __read_number($(tr).find('.credit'));
                    var debit = __read_number($(tr).find('.debit'));

                    if (credit != 0 || debit != 0) {
                        if ($(tr).find('.account_id').val() == '') {
                            is_valid = false;
                            alert("@lang('accounting::lang.select_all_accounts')");
                        }
                    }
                });

                if (is_valid) {
                    $('form#journal_add_form').submit();
                }

                return is_valid;
            });

            $('.credit').change(function() {
                if ($(this).val() > 0) {
                    $(this).parents('tr').find('.debit').val('');
                }
                calculate_total();
            });
            $('.debit').change(function() {
                if ($(this).val() > 0) {
                    $(this).parents('tr').find('.credit').val('');
                }
                calculate_total();
            });
        });

        function calculate_total() {
            var total_credit = 0;
            var total_debit = 0;
            $('table > tbody  > tr').each(function(index, tr) {
                var credit = __read_number($(tr).find('.credit'));
                total_credit += credit;

                var debit = __read_number($(tr).find('.debit'));
                total_debit += debit;
            });

            $('.total_credit_hidden').val(total_credit);
            $('.total_debit_hidden').val(total_debit);

            $('.total_credit').text(__currency_trans_from_en(total_credit));
            $('.total_debit').text(__currency_trans_from_en(total_debit));
        }
        $(document).on("click", ".fa-trash", function() {
            // console.log("amen");
            var tbode_number = $(this).val();
            let counter = $('#auto_migration_table' + tbode_number + ' tr').length - 1;
            console.log(counter);
            if (counter > 1) {
                $(this).parents("tr").remove();


            }

        })
        $(document).on('click', '.fa-plus-square', function() {
            var tbode_number = $(this).val();
            let counter = $('#auto_migration_table' + tbode_number + ' tr').length - 1;
            $('#tbody' + tbode_number).append(
                '<tr><td style="display: flex;font-size: smaller;align-items:center"><button type="button" class="fa fa-trash fa-2x cursor-pointer" data-id="' +
                counter +
                '" name="' + tbode_number + '" value="' + tbode_number +
                '" style="background: transparent; border: 0px;color: red;font-size: small;"></button><button type="button" class="fa fa-plus-square fa-2x text-primary cursor-pointer" data-id="' +
                counter +
                '" name="' + tbode_number + '" value="' + tbode_number +
                '" style="background: transparent; border: 0px;"></button></td><td><select class="form-control accounts-dropdown account_id" required style="width: 100%;" name="account_id' +
                tbode_number + '[' +
                counter +
                ']"><option selected="selected" value="">يرجى الاختيار</option></select> </td> <td><label class="radio-inline"><input value="debit" type="radio" name="type' +
                tbode_number + '[' +
                counter +
                ']" checked>@lang('accounting::lang.debtor')</label><label class="radio-inline"><input value="credit" type="radio" name="type' +
                tbode_number + '[' +
                counter +
                ']">@lang('accounting::lang.creditor')</label></td><td><select class="form-control" name="amount_type' +
                tbode_number + '[' + counter + ']' + '" id="amount_type' + tbode_number + '' + counter +
                '"style="padding: 3px" required><option value="final_total">@lang('accounting::lang.autoMigration.final_total')</option><option value="total_before_tax">@lang('accounting::lang.autoMigration.total_before_tax')</option><option value="tax_amount">@lang('accounting::lang.autoMigration.tax_amount')</option><option value="shipping_charges">@lang('accounting::lang.autoMigration.shipping_charges')</option><option value="discount_amount">@lang('accounting::lang.autoMigration.discount_amount')</option></select></td></tr>'
            )
            $('select[name="account_id' + tbode_number + '[' + counter + ']"]').select2({
                ajax: {
                    url: '{{ route('accounts-dropdown') }}',
                    dataType: 'json',
                    processResults: function(data) {
                        return {
                            results: data
                        }
                    },
                },
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateResult: function(data) {
                    return data.html;
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });
            $('.credit').change(function() {
                if ($(this).val() > 0) {
                    $(this).parents('tr').find('.debit').val('');
                }
                calculate_total();
            });
            $('.debit').change(function() {
                if ($(this).val() > 0) {
                    $(this).parents('tr').find('.credit').val('');
                }
                calculate_total();
            });

        });
    </script>
@endsection
