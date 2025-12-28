<div class="modal fade" id="create_receipt_voucher_modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {!! Form::open(['method' => 'post', 'id' => 'create_receipt_voucher_form']) !!}
            @csrf
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    @lang('accounting::lang.create_receipt_voucher')
                </h4>
            </div>

            <div class="modal-body">
                <div class="row payment_row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('customer', __('accounting::lang.customer') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fas fa-money-bill-alt"></i>
                                </span>
                                <select class="form-control select2" style="width: 100%" name="contact_id"
                                    id="contact_id" required>
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach ($contacts as $contact)
                                        <option value="{{ $contact['id'] }}">
                                            {{ $contact['name'] . ' - ' . $contact['supplier_business_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="receipt_type_value"
                                    style="width: 100%; padding-left: 14px; padding-right: 14px">{{ __('accounting::lang.receipt_type') }}</label>
                                <div class="" style="width: 100%; padding-left: 14px; padding-right: 14px">
                                    <label class="radio-inline px-2">
                                        <input value="value" type="radio" name="receipt_type" id="receipt_type_value"
                                            checked>@lang('accounting::lang.value')
                                    </label>
                                    <label class="radio-inline px-2">
                                        <input value="voucher" type="radio" name="receipt_type"
                                            id="receipt_type_voucher">@lang('accounting::lang.voucher')
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="transaction_id"
                                id="transaction_id_label">{{ __('accounting::lang.customer_vouchers') }}</label>

                            <select class="form-control select2" style="width: 100%" name="transaction_id"
                                id="transaction_id">
                                <option value="">@lang('messages.please_select')</option>
                            </select>

                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('amount', __('sale.amount') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fas fa-money-bill-alt"></i>
                                </span>
                                {!! Form::number('amount', '', [
                                    'class' => 'form-control input_number payment_amount',
                                    'step' => '0.1',
                                    'id' => 'amount',
                                    'required',
                                    'placeholder' => __('sale.amount'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('method', __('purchase.payment_method') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fas fa-money-bill-alt"></i>
                                </span>
                                {!! Form::select('method', $payment_types, null, [
                                    'class' => 'form-control select2 payment_types_dropdown',
                                    'required',
                                    'style' => 'width:100%;',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('paid_on', __('lang_v1.paid_on') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('paid_on', null, ['class' => 'form-control datetimepicker', 'required']) !!}
                            </div>
                        </div>
                    </div>
                    @php
                        $pos_settings = !empty(session()->get('business.pos_settings'))
                            ? json_decode(session()->get('business.pos_settings'), true)
                            : [];

                        $enable_cash_denomination_for_payment_methods = !empty(
                            $pos_settings['enable_cash_denomination_for_payment_methods']
                        )
                            ? $pos_settings['enable_cash_denomination_for_payment_methods']
                            : [];
                    @endphp
                    <div class="clearfix"></div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                            {!! Form::file('document', [
                                'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types'))),
                            ]) !!}
                            <p class="help-block">
                                @includeIf('components.document_help_text')</p>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    {{--                    @include('transaction_payment.payment_type_details') --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('note', __('lang_v1.payment_note') . ':') !!}
                            {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3]) !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.add')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>

            {!! Form::close() !!}
        </div>


    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
