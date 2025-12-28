<div class="modal fade" id="edit_opening_balance_modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {!! Form::open(['method' => 'put', 'id' => 'edit_opening_balance_form']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:red"><span
                        aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> @lang('accounting::lang.edit_opening_balance')
                </h4>
            </div>

            <div class="modal-body">
                <div class="form-group row" style="margin-top: 12px">
                    <div class="col-md-3">
                        {!! Form::label('edit_year', __('accounting::lang.year') . '  ', ['style' => 'margin-top:8px']) !!}<span style="color: red; font-size:10px"> *</span>
                    </div>
                    <div class="col-md-5">
                        <?php $years = range(strftime('%Y', time()), 1900); ?>
                        <select class="form-control select2" style="width: 100%;" name="year" id="edit_year"
                            required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row" style="margin-top: 12px">
                    <input type="hidden" name="id" id="opening_balance_id">
                    <div class="col-md-3">
                        {!! Form::label('edit_account_name', __('accounting::lang.account_name') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                    </div>
                    <div class="col-md-5">
                        <select class="form-control select2" style="width: 100%" name="accounting_account_id"
                            id="edit_account_name" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($sub_types as $sub_type)
                                <option value="{{ $sub_type['id'] }}">{{ $sub_type['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row" style="margin-top: 12px">
                    <div class="col-md-3">
                        {!! Form::label('edit_type', __('accounting::lang.operation_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                    </div>
                    <div class="col-md-5">
                        <div style="width: 100%">
                            <label class="radio-inline">
                                <input id="type_debtor" value="debit" type="radio" name="type"
                                    checked>@lang('accounting::lang.debtor')
                            </label>
                            <label class="radio-inline">
                                <input id="type_creditor" value="credit" type="radio"
                                    name="type">@lang('accounting::lang.creditor')
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group row" style="margin-top: 12px">
                    <div class="col-md-3">
                        {!! Form::label('edit_value', __('accounting::lang.charge_value') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                    </div>
                    <div class="col-md-5">
                        {!! Form::number('value', '', [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => __('accounting::lang.charge_value'),
                            'id' => 'edit_value',
                        ]) !!}
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: flex-end">
                <button type="submit" class="btn btn-primary"
                    style="border-radius: 5px;
                min-width: 25%;">@lang('messages.update')</button>
                {{-- <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button> --}}
            </div>

            {!! Form::close() !!}
        </div>



    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
