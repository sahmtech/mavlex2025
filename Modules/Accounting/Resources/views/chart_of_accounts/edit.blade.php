<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('\Modules\Accounting\Http\Controllers\CoaController@update', $account->id),
            'method' => 'put',
            'id' => 'create_client_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:red"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"> <i class="fas fa-edit"></i> @lang('accounting::lang.edit_account')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">

                    {{-- <div class="form-group">
                        {!! Form::label('account_primary_type', __('accounting::lang.account_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        <select class="form-control" name="account_primary_type" id="account_primary_type" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($account_types as $account_type => $account_details)
                                <option value="{{ $account_type }}" @if ($account->account_primary_type == $account_type) selected @endif>
                                    {{ __('accounting::lang.' . $account_type) }}</option>
                            @endforeach
                        </select>
                    </div> --}}

                    {{-- <div class="form-group">
                        {!! Form::label('account_sub_type', __('accounting::lang.account_sub_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        <select class="form-control" name="account_sub_type_id" id="account_sub_type" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($account_sub_types as $account_type)
                                <option value="{{ $account_type->id }}"
                                    data-show_balance="{{ $account_type->show_balance }}"
                                    @if ($account->account_sub_type_id == $account_type->id) selected @endif>
                                    {{ $account_type->account_type_name }}</option>
                            @endforeach
                        </select>
                    </div> --}}

                    {{-- <div class="form-group">
                        {!! Form::label('detail_type', __('accounting::lang.detail_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>

                        <select class="form-control" name="detail_type_id" id="detail_type" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($account_detail_types as $detail_type)
                                <option value="{{ $detail_type->id }}"
                                    @if ($account->detail_type_id == $detail_type->id) selected @endif>
                                    {{ $detail_type->account_type_name }}</option>
                            @endforeach
                        </select>
                        <p class="help-block" id="detail_type_desc">
                            {{ $account->detail_type->account_type_description ?? '' }}</p>
                    </div> --}}

                    <div class="form-group">
                        {!! Form::label('account_category', __('accounting::lang.account_category') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        <select class="form-control" name="account_category" id="account_category" style="padding: 2px"
                            required>
                            <option value="balance_sheet" @if ($account->account_category == 'balance_sheet') selected @endif>
                                @lang('accounting::lang.balance_sheet')</option>
                            <option value="income_list" @if ($account->account_category == 'income_list') selected @endif>
                                @lang('accounting::lang.income_list')</option>
                        </select>
                    </div>

                    <div class="form-group">
                        {!! Form::label('name', __('user.name') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        {!! Form::text('name', $account->name, [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => __('user.name'),
                        ]) !!}
                    </div>

                    {{-- <div class="form-group">
                        {!! Form::label('gl_code', __('accounting::lang.gl_code') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        {!! Form::text('gl_code', $account->gl_code, [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => __('accounting::lang.gl_code'),
                        ]) !!}
                        <p class="help-block">@lang('accounting::lang.gl_code_help')</p>
                    </div> --}}

                    {{-- <div class="form-group">
                        {!! Form::label('parent_account', __('accounting::lang.parent_account') . ':') !!}
                        <select class="form-control" name="parent_account_id" id="parent_account">
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($parent_accounts as $parent_account)
                                <option value="{{ $parent_account->id }}"
                                    @if ($account->parent_account_id == $parent_account->id) selected @endif>
                                    {{ $parent_account->name }}</option>
                            @endforeach
                        </select>
                    </div> --}}
                </div>
            </div>
            {{-- <div class="row"">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('description', __('lang_v1.description') . ':') !!}
                        {!! Form::textarea('description', $account->description, [
                            'class' => 'form-control',
                            'placeholder' => __('lang_v1.description'),
                        ]) !!}
                    </div>
                </div>
            </div> --}}
        </div>

        <div class="modal-footer" style="justify-content: flex-end">
            <button type="submit" class="btn btn-primary"
                style="border-radius: 5px;
            min-width: 25%;">@lang('messages.save')</button>
            {{-- <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button> --}}
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
