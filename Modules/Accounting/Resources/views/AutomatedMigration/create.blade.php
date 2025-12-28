<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">



        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:red"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><i class="fas fa-plus"></i> @lang('accounting::lang.add_auto_migration')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">

                    <section class="content">

                        {!! Form::open([
                            'url' => action('\Modules\Accounting\Http\Controllers\AutomatedMigrationController@store'),
                            'method' => 'post',
                            'id' => 'journal_add_form',
                        ]) !!}

                        @component('components.widget', ['class' => 'box-primary'])
                            <div class="row">
                                <div class="col-sm-3" style="margin-bottom: 5px;">
                                    {!! Form::label('business_location', __('accounting::lang.autoMigration.business_location') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                                    <select class="form-control" name="business_location_id"
                                        id="business_location"style="padding: 3px" required>
                                        <option value="">@lang('messages.please_select')</option>
                                        @foreach ($business_locations as $business_location)
                                            <option value="{{ $business_location->id }}">{{ $business_location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">


                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('name_ar', __('accounting::lang.migration_name') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                                        {!! Form::text('migration_name', '', [
                                            'class' => 'form-control',
                                            'required',
                                            'placeholder' => __('accounting::lang.migration_name'),
                                            'id' => 'name_ar',
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

                                    {!! Form::label('account_sub_type', __('accounting::lang.operatio_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                                    <select class="form-control" name="type" id="account_sub_type"style="padding: 3px"
                                        required>
                                        <option value="">@lang('messages.please_select')</option>
                                        <option value="sell">@lang('accounting::lang.autoMigration.sell')</option>
                                        <option value="sell_return">@lang('accounting::lang.autoMigration.sell_return')</option>
                                        <option value="opening_stock">@lang('accounting::lang.autoMigration.opening_stock')</option>
                                        <option value="purchase">@lang('accounting::lang.autoMigration.purchase_')</option>
                                        <option value="purchase_order">@lang('accounting::lang.autoMigration.purchase_order')</option>
                                        <option value="purchase_return">@lang('accounting::lang.autoMigration.purchase_return')</option>
                                        <option value="expens">@lang('accounting::lang.autoMigration.expens_')</option>
                                        <option value="sell_transfer">@lang('accounting::lang.autoMigration.sell_transfer')</option>
                                        <option value="purchase_transfer">@lang('accounting::lang.autoMigration.purchase_transfer')</option>
                                        <option value="payroll">@lang('accounting::lang.autoMigration.payroll')</option>
                                        <option value="opening_balance">@lang('accounting::lang.autoMigration.opening_balance')</option>
                                    </select>
                                </div>

                                <div class="col-sm-3">
                                    {!! Form::label('account_sub_type', __('accounting::lang.payment_stauts') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                                    <select class="form-control" name="payment_status" id="account_sub_type"
                                        style="padding: 3px" required>
                                        <option value="">@lang('messages.please_select')</option>
                                        <option value="paid">@lang('accounting::lang.autoMigration.paid')</option>
                                        <option value="due">@lang('accounting::lang.autoMigration.due')</option>
                                        <option value="partial">@lang('accounting::lang.autoMigration.partial')</option>
                                    </select>
                                </div>

                                <div class="col-sm-3">
                                    {!! Form::label('account_sub_type', __('accounting::lang.payment_method') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                                    <select class="form-control" name="method" id="account_sub_type"style="padding: 3px"
                                        required>
                                        <option value="">@lang('messages.please_select')</option>
                                        <option value="cash">@lang('accounting::lang.autoMigration.cash')</option>
                                        <option value="card">@lang('accounting::lang.autoMigration.card')</option>
                                        <option value="bank_transfer">@lang('accounting::lang.autoMigration.bank_transfer')</option>
                                        <option value="cheque">@lang('accounting::lang.autoMigration.cheque')</option>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-sm-12">
                                        <h4 style="text-align: start">@lang('accounting::lang.first_journal')<span
                                                style="color: red; font-size:10px"> *</span></h4>


                                        <table class="table table-bordered table-striped hide-footer" id="journal_table1">
                                            <thead>
                                                <tr>
                                                    <th class="col-md-1">#
                                                    </th>
                                                    <th class="col-md-3">@lang('accounting::lang.account')</th>
                                                    <th class="col-md-3">@lang('accounting::lang.debit') / @lang('accounting::lang.credit')</th>
                                                    <th class="col-md-3">@lang('accounting::lang.amount')</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody1">
                                                <tr>
                                                    <td style="display: flex;font-size: smaller;align-items:center">
                                                        {{-- <a type="button" class="fa fa-trash fa-2x cursor-pointer"
                                                            data-id="1" name="1"
                                                            style="background: transparent; border: 0px;color: red;
                                                    font-size: small;"></a> --}}
                                                        <button type="button"
                                                            class="fa fa-plus-square fa-2x text-primary cursor-pointer"
                                                            data-id="1" name="1" value="1"
                                                            style="    background: transparent; border: 0px;"></button>
                                                    </td>
                                                    <td>
                                                        {{-- {!! Form::select('account_id1[' . 1 . ']', [], null, [
                                                                'class' => 'form-control accounts-dropdown account_id',
                                                                'placeholder' => __('messages.please_select'),
                                                                'style' => 'width: 100%; padding:3px;',
                                                            ]) !!} --}}
                                                        <select class="form-control accounts-dropdown account_id"
                                                            style="width: 100%;" name="account_id1[1]" required>
                                                            <option selected="selected" value="">يرجى الاختيار
                                                            </option>
                                                        </select>
                                                    </td>

                                                    <td>

                                                        <label class="radio-inline">
                                                            <input value="debit" type="radio" name="type1[1]"
                                                                checked>@lang('accounting::lang.debtor')
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input value="credit" type="radio"
                                                                name="type1[1]">@lang('accounting::lang.creditor')
                                                        </label>

                                                    </td>

                                                    <td>
                                                        <select class="form-control" name="amount_type1[1]"
                                                            id="account_sub_type"style="padding: 3px" required>
                                                            <option value="final_total">@lang('accounting::lang.autoMigration.final_total')</option>
                                                            <option value="total_before_tax">@lang('accounting::lang.autoMigration.total_before_tax')</option>
                                                            <option value="tax_amount">@lang('accounting::lang.autoMigration.tax_amount')</option>
                                                            <option value="shipping_charges">@lang('accounting::lang.autoMigration.shipping_charges')</option>
                                                            <option value="discount_amount">@lang('accounting::lang.autoMigration.discount_amount')</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </tbody>

                                            <tfoot>
                                                <tr>
                                                    <th></th>
                                                    <th class="text-center">@lang('accounting::lang.total')</th>
                                                    <th><input type="hidden" class="total_debit_hidden"><span
                                                            class="total_debit"></span></th>
                                                    <th><input type="hidden" class="total_credit_hidden"><span
                                                            class="total_credit"></span>
                                                    </th>
                                                </tr>
                                            </tfoot>
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
                                                    <th class="col-md-3">@lang('accounting::lang.debit') / @lang('accounting::lang.credit')</th>
                                                    <th class="col-md-3">@lang('accounting::lang.amount')</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody2">
                                                <tr>

                                                    <td style="display: flex;font-size: smaller;align-items:center">
                                                        {{-- <a type="button" class="fa fa-trash fa-2x cursor-pointer"
                                                            data-id="2" name="2"
                                                            style="background: transparent; border: 0px;color: red;
                                                        font-size: small;"></a> --}}
                                                        <button type="button"
                                                            class="fa fa-plus-square fa-2x text-primary cursor-pointer"
                                                            data-id="1" name="2" value="2"
                                                            style="    background: transparent; border: 0px;"></button>
                                                    </td>
                                                    <td>
                                                        {{-- {!! Form::select('account_id2[' . 1 . ']', [], null, [
                                                                'class' => 'form-control accounts-dropdown account_id',
                                                                'placeholder' => __('messages.please_select'),
                                                                'style' => 'width: 100%; padding:3px;',
                                                            ]) !!} --}}
                                                        <select class="form-control accounts-dropdown account_id"
                                                            style="width: 100%;" name="account_id2[1]">
                                                            <option selected="selected" value="">يرجى الاختيار
                                                            </option>
                                                        </select>
                                                    </td>

                                                    <td>


                                                        <label class="radio-inline">
                                                            <input value="debit" type="radio" name="type2[1]"
                                                                checked>@lang('accounting::lang.debtor')
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input value="credit" type="radio"
                                                                name="type2[1]">@lang('accounting::lang.creditor')
                                                        </label>

                                                    </td>


                                                    <td>
                                                        <select class="form-control" name="amount_type2[1]"
                                                            id="account_sub_type"style="padding: 3px" required>
                                                            <option value="final_total">@lang('accounting::lang.autoMigration.final_total')</option>
                                                            <option value="total_before_tax">@lang('accounting::lang.autoMigration.total_before_tax')</option>
                                                            <option value="tax_amount">@lang('accounting::lang.autoMigration.tax_amount')</option>
                                                            <option value="shipping_charges">@lang('accounting::lang.autoMigration.shipping_charges')</option>
                                                            <option value="discount_amount">@lang('accounting::lang.autoMigration.discount_amount')</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </tbody>

                                            <tfoot>
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
                                            </tfoot>
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
        </div>

    </div> <!-- /.modal-content -->
</div><!-- /.modal-dialog -->
