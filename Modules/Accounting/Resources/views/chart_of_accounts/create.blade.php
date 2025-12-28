
<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('\Modules\Accounting\Http\Controllers\CoaController@store'),
            'method' => 'post',
            'id' => 'create_client_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:red"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><i class="fas fa-plus"></i> @lang('accounting::lang.add_account')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">

                    {{-- <div class="form-group">
                        {!! Form::label('account_primary_type', __('accounting::lang.account_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        <select class="form-control" name="account_primary_type" id="account_primary_type" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach ($account_types as $account_type => $account_details)
                                <option value="{{ $account_type }}"
                                    style="font-size: 14px !important;
                                line-height: 1.42857143;
                                color: #555;">
                                    {{ __('accounting::lang.' . $account_type) }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}

                    {{-- <div class="form-group">
                        {!! Form::label('account_sub_type', __('accounting::lang.account_sub_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        <select class="form-control" name="account_sub_type_id" id="account_sub_type" required>
                            <option value="">@lang('messages.please_select')</option>
                        </select>
                    </div> --}}



                    {{-- <div class="form-group">
                        {!! Form::label('detail_type', __('accounting::lang.detail_type') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        {!! Form::select('detail_type_id', [], null, [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => __('messages.please_select'),
                            'id' => 'detail_type',
                        ]) !!}
                        <p class="help-block" id="detail_type_desc"></p>
                    </div> --}}

                    {{-- <div class="form-group">
                        {!! Form::label('parent_account', __('accounting::lang.parent_account') . '') !!}
                        {!! Form::select('parent_account_id', [], null, [
                            'class' => 'form-control',
                            'placeholder' => __('messages.please_select'),
                            'id' => 'parent_account',
                        ]) !!}
                    </div> --}}

                    <div class="form-group">
                        {!! Form::label('account_category', __('accounting::lang.account_category') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        <select class="form-control" name="account_category" id="account_category" style="padding: 2px"
                            required>
                            <option value="balance_sheet"
                                style="font-size: 14px !important;
                            line-height: 1.42857143;
                            color: #555;">
                                @lang('accounting::lang.balance_sheet')</option>
                            <option value="income_list"
                                style="font-size: 14px !important;
                            line-height: 1.42857143;
                            color: #555;
                           ">
                                @lang('accounting::lang.income_list')</option>
                        </select>
                    </div>

                    <div class="form-group">
                        {!! Form::label('name', __('user.name') . '  ') !!}<span style="color: red; font-size:10px"> *</span>
                        {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('user.name')]) !!}
                    </div>

                    {{-- <div class="form-group">
                        {!! Form::label('gl_code', __('accounting::lang.gl_code') . '  ') !!} <span style="color: red; font-size:10px"> *</span>
                        {!! Form::text('gl_code', null, [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => __('accounting::lang.gl_code'),
                        ]) !!}
                        <p class="help-block">@lang('accounting::lang.gl_code_help')</p>
                    </div> --}}

                    <input name="parent_account_id" value="{{ $parent_accounts }}" hidden />


                </div>
            </div>
            <div class="row" id="bal_div">
                {{-- <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('balance', __( 'lang_v1.balance' ) . ':') !!}
                    {!! Form::text('balance', null, ['class' => 'form-control input_number', 
                        'placeholder' => __( 'lang_v1.balance' ) ]); !!}
                </div>
            </div> --}}
                {{-- <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('as_of', __('accounting::lang.as_of') . ':') !!}
                        <div class="input-group">
                            {!! Form::text('balance_as_of', null, ['class' => 'form-control', 'id' => 'as_of']) !!}
                            <span class="input-group-addon"><i class="fas fa-calendar"></i></span>
                        </div>
                    </div>
                </div>
            </div> --}}
                {{-- <div class="row"">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('description', __('lang_v1.description') . ':') !!}
                        {!! Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.description')]) !!}
                    </div>
                </div>
            </div> --}}
            </div>

            <div class="modal-footer" style="justify-content: flex-end">
                <button type="submit" class="btn btn-primary"
                    style="    border-radius: 5px;
                min-width: 25%;">@lang('messages.save')</button>
                {{-- <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button> --}}
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
