@extends('layouts.app')

@section('title', __('accounting::lang.automatedMigration'))

@section('content')

    {{-- @include('accounting::layouts.nav') --}}

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('messages.edit') @lang('accounting::lang.automatedMigration')</h1>
    </section>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <section class="content">
                    {!! Form::open([
                        'url' => action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@update', $mappingSetting->id),
                        'method' => 'put',
                        'id' => 'update_auto_migration',
                    ]) !!}
                    @component('components.widget', ['class' => 'box-primary'])
                        <div class="row">
                            <div class="col-sm-3" style="margin-bottom: 5px;">
                                {!! Form::label('business_location', __('accounting::lang.autoMigration.business_location') . '  ') !!}



                                {!! Form::text('business_location_id', $mappingSetting?->businessLocation?->name, [
                                    'class' => 'form-control',
                                    'required',
                                
                                    'id' => 'business_location_id',
                                    'readonly',
                                ]) !!}


                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('name_ar', __('accounting::lang.migration_name') . '  ') !!}
                                    {!! Form::text('migration_name', __('accounting::lang.' . $mappingSetting->name), [
                                        'class' => 'form-control',
                                        'required',
                                        'placeholder' => __('accounting::lang.migration_name'),
                                        'id' => 'name_ar',
                                        'readonly',
                                    ]) !!}
                                </div>
                            </div>

                            <div hidden>
                                {!! Form::text('journal_date', @format_datetime('now'), [
                                    'class' => 'form-control datetimepicker',
                                    'readonly',
                                ]) !!}

                            </div>

                            <div class="col-sm-3">

                                {!! Form::label('account_sub_type', __('accounting::lang.operatio_type') . '  ') !!}
                                {!! Form::text('type', __('accounting::lang.autoMigration.' . $mappingSetting->type), [
                                    'class' => 'form-control',
                                    'required',
                                
                                    'id' => 'type',
                                    'readonly',
                                ]) !!}

                            </div>

                            <div class="col-sm-3">
                                {!! Form::label('account_sub_type', __('accounting::lang.payment_stauts') . '  ') !!}
                                {!! Form::text('payment_status', __('accounting::lang.autoMigration.' . $mappingSetting->payment_status), [
                                    'class' => 'form-control',
                                    'required',
                                
                                    'id' => 'payment_status',
                                    'readonly',
                                ]) !!}

                            </div>

                            <div class="col-sm-3">
                                {!! Form::label('account_sub_type', __('accounting::lang.payment_method') . '  ') !!}
                                {!! Form::text('method', __('accounting::lang.autoMigration.' . $mappingSetting->method), [
                                    'class' => 'form-control',
                                    'required',
                                
                                    'id' => 'method',
                                    'readonly',
                                ]) !!}

                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <h4 style="text-align: start">@lang('accounting::lang.first_journal')<span style="color: red; font-size:10px">
                                            *</span></h4>

                                    <table class="table table-bordered table-striped hide-footer" id="journal_table1">
                                        <thead>
                                            <tr>
                                                <th class="col-md-1">#
                                                </th>
                                                <th class="col-md-3">@lang('accounting::lang.account')</th>
                                                <th class="col-md-2">@lang('accounting::lang.cost_center')</th>

                                                <th class="col-md-2">@lang('accounting::lang.debit') / @lang('accounting::lang.credit')</th>
                                                <th class="col-md-3">@lang('accounting::lang.amount')</th>
                                                {{-- <th class="col-md-3">@lang('accounting::lang.deposetTo_account')</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody id="tbody1">
                                            @foreach ($journal_entry_1 as $index => $journal_entry)
                                                <tr>
                                                    <td style="display: flex;font-size: smaller;align-items:center">
                                                        @if (auth()->user()->can('Admin#1') ||
                                                                auth()->user()->can('superadmin') ||
                                                                auth()->user()->can('accounting.destroy_acc_trans_mapping_setting'))
                                                            <a type="button" class="fa fa-trash fa-2x cursor-pointer"
                                                                href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@destroy_acc_trans_mapping_setting', $journal_entry->id) }}"
                                                                data-href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@destroy_acc_trans_mapping_setting', $journal_entry->id) }}"
                                                                data-id="1" name="1" value="{{ $journal_entry->id }}"
                                                                style="background: transparent; border: 0px;color: red;
                                                            font-size: small;"></a>
                                                        @endif
                                                        <button type="button"
                                                            class="fa fa-plus-square fa-2x text-primary cursor-pointer"
                                                            data-id="1" name="1" value="1"
                                                            style="    background: transparent; border: 0px;"></button>

                                                    </td>

                                                    <td>

                                                        <select class="form-control accounts-dropdown account_id"
                                                            style="width: 100%;" name="account_id1[{{ $index + 1 }}]">
                                                            <option value="">يرجى الاختيار
                                                            </option>
                                                            <option value="{{ $journal_entry->accounting_account_id }}"
                                                                selected>

                                                                {{ $journal_entry->account_name }} - <small class="text-muted">
                                                                    @lang('accounting::lang.' . $journal_entry->account_primary_type)
                                                                    -
                                                                    @lang('accounting::lang.' . $journal_entry->account_sub_type)</small>
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select class="form-control cost_center" style="width: 100%;"
                                                            name="cost_center1[{{ $index + 1 }}]">
                                                            <option selected="selected" value="">يرجى الاختيار</option>
                                                            @foreach ($allCenters as $allCenter)
                                                                <option @if ($journal_entry->cost_center_id == $allCenter->id) selected @endif
                                                                    value="{{ $allCenter->id }}">{{ $allCenter->ar_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>

                                                        <label class="radio-inline">
                                                            <input value="debit" type="radio"
                                                                name="type1[{{ $index + 1 }}]"
                                                                @if ($journal_entry->type == 'debit') checked @endif>@lang('accounting::lang.debtor')
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input value="credit" type="radio"
                                                                name="type1[{{ $index + 1 }}]"
                                                                @if ($journal_entry->type == 'credit') checked @endif>@lang('accounting::lang.creditor')
                                                        </label>

                                                    </td>

                                                    <td>
                                                        <select class="form-control" name="amount_type1[{{ $index + 1 }}]"
                                                            id="account_sub_type"style="padding: 3px" required>
                                                            <option value="final_total"
                                                                @if ($journal_entry->amount == 'final_total') selected @endif>
                                                                @lang('accounting::lang.autoMigration.final_total')</option>
                                                            <option value="total_before_tax"
                                                                @if ($journal_entry->amount == 'total_before_tax') selected @endif>
                                                                @lang('accounting::lang.autoMigration.total_before_tax')</option>
                                                            <option value="tax_amount"
                                                                @if ($journal_entry->amount == 'tax_amount') selected @endif>
                                                                @lang('accounting::lang.autoMigration.tax_amount')</option>
                                                            <option value="shipping_charges"
                                                                @if ($journal_entry->amount == 'shipping_charges') selected @endif>
                                                                @lang('accounting::lang.autoMigration.shipping_charges')</option>
                                                            <option value="discount_amount"
                                                                @if ($journal_entry->amount == 'discount_amount') selected @endif>
                                                                @lang('accounting::lang.autoMigration.discount_amount')</option>
                                                        </select>
                                                    </td>

                                                    {{-- <td>

                                                        <select class="form-control accounts-dropdown account_id"
                                                            style="width: 100%;" name="account_id1[{{ $index + 1 }}]">
                                                            <option value="">يرجى الاختيار
                                                            </option>
                                                            <option value="{{ $journal_entry->accounting_account_id }}"
                                                                selected>

                                                                {{ $journal_entry->account_name }} - <small
                                                                    class="text-muted">
                                                                    @lang('accounting::lang.' . $journal_entry->account_primary_type)
                                                                    -
                                                                    @lang('accounting::lang.' . $journal_entry->account_sub_type)</small>
                                                            </option>
                                                        </select>
                                                    </td> --}}
                                                </tr>
                                            @endforeach
                                        </tbody>

                                        {{-- <tfoot>
                                            <tr>
                                                <th></th>
                                                <th class="text-center">@lang('accounting::lang.total')</th>
                                                <th><input type="hidden" class="total_debit_hidden"><span
                                                        class="total_debit"></span></th>
                                                <th><input type="hidden" class="total_credit_hidden"><span
                                                        class="total_credit"></span>
                                                </th>
                                            </tr>
                                        </tfoot> --}}
                                    </table>

                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12">
                                    <h4 style="text-align: start">@lang('accounting::lang.second_journal')</h4>

                                    <table class="table table-bordered table-striped hide-footer" id="journal_table2">
                                        <thead>
                                            <tr>
                                                <th class="col-md-1">#
                                                </th>
                                                <th class="col-md-3">@lang('accounting::lang.account')</th>
                                                <th class="col-md-2">@lang('accounting::lang.cost_center')</th>

                                                <th class="col-md-3">@lang('accounting::lang.debit') / @lang('accounting::lang.credit')</th>
                                                <th class="col-md-3">@lang('accounting::lang.amount')</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody2">
                                            @foreach ($journal_entry_2 as $index => $journal_entry)
                                                <tr>
                                                    <td style="display: flex;font-size: smaller;align-items:center">
                                                        @if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ||
                                                                auth()->user()->can('superadmin') ||
                                                                auth()->user()->can('accounting.destroy_acc_trans_mapping_setting'))
                                                            <a type="button" class="fa fa-trash fa-2x cursor-pointer"
                                                                href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@destroy_acc_trans_mapping_setting', $journal_entry->id) }}"
                                                                data-href="{{ action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@destroy_acc_trans_mapping_setting', $journal_entry->id) }}"
                                                                data-id="2" name="2" value="{{ $journal_entry->id }}"
                                                                style="background: transparent; border: 0px;color: red;
                                                            font-size: small;"></a>
                                                        @endif
                                                        <button type="button"
                                                            class="fa fa-plus-square fa-2x text-primary cursor-pointer"
                                                            data-id="1" name="2" value="2"
                                                            style="    background: transparent; border: 0px;"></button>

                                                    </td>



                                                    <td>

                                                        <select class="form-control accounts-dropdown account_id"
                                                            style="width: 100%;" name="account_id2[{{ $index + 1 }}]">
                                                            <option value="">يرجى الاختيار
                                                            </option>

                                                            <option value="{{ $journal_entry->accounting_account_id }}"
                                                                selected>

                                                                {{ $journal_entry->account_name }} - <small
                                                                    class="text-muted">
                                                                    @lang('accounting::lang.' . $journal_entry->account_primary_type)
                                                                    -
                                                                    @lang('accounting::lang.' . $journal_entry->account_sub_type)</small>
                                                            </option>


                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select class="form-control cot_center2" style="width: 100%;"
                                                            name="cost_center2[{{ $index + 1 }}]">
                                                            <option selected="selected" value="">يرجى الاختيار</option>
                                                            @foreach ($allCenters as $allCenter)
                                                                <option @if ($journal_entry->cost_center_id == $allCenter->id) selected @endif
                                                                    value="{{ $allCenter->id }}">
                                                                    {{ $allCenter->ar_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>


                                                        <label class="radio-inline">
                                                            <input value="debit" type="radio"
                                                                name="type2[{{ $index + 1 }}]"
                                                                @if ($journal_entry->type == 'debit') checked @endif>@lang('accounting::lang.debtor')
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input value="credit" type="radio"
                                                                name="type2[{{ $index + 1 }}]"
                                                                @if ($journal_entry->type == 'credit') checked @endif>@lang('accounting::lang.creditor')
                                                        </label>

                                                    </td>


                                                    <td>
                                                        <select class="form-control" name="amount_type2[{{ $index + 1 }}]"
                                                            id="account_sub_type"style="padding: 3px" required>
                                                            <option value="final_total"
                                                                @if ($journal_entry->amount == 'final_total') selected @endif>
                                                                @lang('accounting::lang.autoMigration.final_total')</option>
                                                            <option value="total_before_tax"
                                                                @if ($journal_entry->amount == 'total_before_tax') selected @endif>
                                                                @lang('accounting::lang.autoMigration.total_before_tax')</option>
                                                            <option value="tax_amount"
                                                                @if ($journal_entry->amount == 'tax_amount') selected @endif>
                                                                @lang('accounting::lang.autoMigration.tax_amount')</option>
                                                            <option value="shipping_charges"
                                                                @if ($journal_entry->amount == 'shipping_charges') selected @endif>
                                                                @lang('accounting::lang.autoMigration.shipping_charges')</option>
                                                            <option value="discount_amount"
                                                                @if ($journal_entry->amount == 'discount_amount') selected @endif>
                                                                @lang('accounting::lang.autoMigration.discount_amount')</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>

                                        {{-- <tfoot>
                                            <tr>
                                                <th></th>
                                                <th class="text-center">@lang('accounting::lang.total')</th>
                                                <th><input type="hidden" class="total_debit_hidden"><span
                                                        class="total_debit"></span>
                                                </th>
                                                <th><input type="hidden" class="total_credit_hidden"><span
                                                        class="total_credit"></span>
                                                </th>
                                            </tr>

                                        </tfoot> --}}
                                    </table>

                                </div>
                            </div>



                            <div class="row">
                                <div class="col-sm-12"
                                    style="display: flex;
                                justify-content: center;">
                                    <button type="submit"
                                        style="    width: 50%;
                                    border-radius: 28px;"
                                        class="btn btn-primary pull-right btn-flat journal_add_btn">@lang('messages.save')</button>
                                </div>
                            </div>
                        </div>
                    @endcomponent

                    {!! Form::close() !!}
                </section>
            </div>
        </div>
    </section>

@stop


@section('javascript')
    @include('accounting::accounting.common_js')

    <script type="text/javascript">
        $(document).ready(function() {
            $('#journal_table tbody').append($(
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
            let counter = $('#journal_table' + tbode_number + ' tr').length - 1;
            console.log(counter);
            if (counter > 1) {
                $(this).parents("tr").remove();


            }

        })
        $(document).ready(function() {
            // console.log("amen");
            // $(this).parents("tr").remove();

            var tbode_number1 = 1
            var tbode_number2 = 2
            let counter1 = $('#journal_table' + tbode_number1 + ' tr').length - 1;
            let counter2 = $('#journal_table' + tbode_number2 + ' tr').length - 1;
            if (counter1 == 0) {
                counter = 1;
                tbode_number = tbode_number1;

                $('#tbody' + tbode_number).append(
                    '<tr><td style="display: flex;font-size: smaller;align-items:center"><button type="button" class="fa fa-trash fa-2x cursor-pointer" data-id="' +
                    counter +
                    '" name="' + tbode_number + '" value="' + tbode_number +
                    '" style="background: transparent; border: 0px;color: red;font-size: small;"></button><button type="button" class="fa fa-plus-square fa-2x text-primary cursor-pointer" data-id="' +
                    counter +
                    '" name="' + tbode_number + '" value="' + tbode_number +
                    '" style="background: transparent; border: 0px;"></button></td><td><select class="form-control accounts-dropdown account_id" style="width: 100%;" name="account_id' +
                    tbode_number + '[' +
                    counter +
                    ']"><option selected="selected" value="">يرجى الاختيار</option></select> </td> <td><select class="form-control cost_center" style="width: 100%;" name="cost_center' +
                    tbode_number + '[' +
                    counter +
                    ']"><option selected="selected" value="">يرجى الاختيار</option> @foreach ($allCenters as $allCenter)<option value="{{ $allCenter->id }}">{{ $allCenter->ar_name }}</option>@endforeach </select> </td><td><label class="radio-inline"><input value="debit" type="radio" name="type' +
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

            }
            if (counter2 == 0) {
                counter = 1;
                tbode_number = tbode_number2;

                $('#tbody' + tbode_number).append(
                    '<tr><td style="display: flex;font-size: smaller;align-items:center"><button type="button" class="fa fa-trash fa-2x cursor-pointer" data-id="' +
                    counter +
                    '" name="' + tbode_number + '" value="' + tbode_number +
                    '" style="background: transparent; border: 0px;color: red;font-size: small;"></button><button type="button" class="fa fa-plus-square fa-2x text-primary cursor-pointer" data-id="' +
                    counter +
                    '" name="' + tbode_number + '" value="' + tbode_number +
                    '" style="background: transparent; border: 0px;"></button></td><td><select class="form-control accounts-dropdown account_id" style="width: 100%;" name="account_id' +
                    tbode_number + '[' +
                    counter +
                    ']"><option selected="selected" value="">يرجى الاختيار</option></select> </td><td><select class="form-control cost_center" style="width: 100%;" name="cost_center' +
                    tbode_number + '[' +
                    counter +
                    ']"><option selected="selected" value="">يرجى الاختيار</option> @foreach ($allCenters as $allCenter)<option value="{{ $allCenter->id }}">{{ $allCenter->ar_name }}</option>@endforeach </select> </td> <td><label class="radio-inline"><input value="debit" type="radio" name="type' +
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

            }
            console.log(counter);
        });
        $(document).on('click', '.fa-plus-square', function() {
            var tbode_number = $(this).val();
            let counter = $('#journal_table' + tbode_number + ' tr').length;
            // console.log(counter);
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
                ']"><option selected="selected" value="">يرجى الاختيار</option></select> </td> <td><select class="form-control cost_center" style="width: 100%;" name="cost_center' +
                tbode_number + '[' +
                counter +
                ']"><option selected="selected" value="">يرجى الاختيار</option> @foreach ($allCenters as $allCenter)<option value="{{ $allCenter->id }}">{{ $allCenter->ar_name }}</option>@endforeach </select> </td><td><label class="radio-inline"><input value="debit" type="radio" name="type' +
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

        })
    </script>
@endsection
