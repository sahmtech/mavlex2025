<div class="modal fade" id="create_opening_balance_modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {!! Form::open(['method' => 'post', 'id' => 'create_opening_balance_form']) !!}
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"
                        style="color:red">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fas fa-plus"></i> @lang('accounting::lang.create_opening_balance')
                </h4>
            </div>

            <div class="modal-body">
                {{-- <div class="form-group row" style="margin-top: 12px">
                    <div class="col-md-3">
                        {!! Form::label('year', __( 'accounting::lang.year' ) . ':*', ['style' => 'margin-top:8px']) !!}
                    </div>
                    <div class="col-md-5">
                        <?php $years = range(strftime('%Y', time()), 1900); ?>
                        <select class="form-control select2" style="width: 100%;" name="year" id="year" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($years as $year)
                                <option value="{{$year}}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> --}}
                <div class="form-group row" style="margin-top: 12px">
                    <div class="col-md-3">
                        {!! Form::label('account_name', __('accounting::lang.account_name') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                    </div>
                    <div class="col-md-5">
                        <select class="form-control select2" style="width: 100%" name="accounting_account_id"
                            id="account_name" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($sub_types as $sub_type)
                                <option value="{{ $sub_type['id'] }}">{{ $sub_type['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row" style="margin-top: 12px">
                    <div class="col-md-3">
                        {!! Form::label('type', __('accounting::lang.operation_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                    </div>
                    <div class="col-md-5">
                        <div style="width: 100%">
                            <label class="radio-inline">
                                <input value="debit" type="radio" name="type" checked>@lang('accounting::lang.debtor')
                            </label>
                            <label class="radio-inline">
                                <input value="credit" type="radio" name="type">@lang('accounting::lang.creditor')
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group row" style="margin-top: 12px">
                    <div class="col-md-3">
                        {!! Form::label('value', __('accounting::lang.charge_value') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                    </div>
                    <div class="col-md-5">
                        {!! Form::number('value', '', [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => __('accounting::lang.charge_value'),
                            'id' => 'value',
                        ]) !!}
                    </div>
                </div>
            </div>
            <div class="modal-footer"style="justify-content: flex-end">
                <button type="submit" class="btn btn-primary"
                    style="    border-radius: 5px;
                min-width: 25%;">@lang('messages.add')</button>
                {{-- <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button> --}}
            </div>

            {!! Form::close() !!}
        </div>


    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
