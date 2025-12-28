@if (empty($only) || in_array('payment_voucher_filter_customer_id', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('payment_voucher_filter_customer_id', __('accounting::lang.supplier') . ':') !!}
            <select class="form-control select2" name="payment_voucher_filter_customer_id"
                id="payment_voucher_filter_customer_id" style="width: 100%">
                <option selected="selected" value="">{{ __('accounting::lang.all') }}</option>
                @foreach ($contacts as $contact)
                    <option value="{{ $contact->id }}">{{ $contact->name . ' - ' . $contact->supplier_business_name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
@endif
@if (empty($only) || in_array('payment_voucher_filter_account_id', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('payment_voucher_filter_account_id', __('accounting::lang.account') . ':') !!}
            <select class="form-control select2" name="payment_voucher_filter_account_id"
                id="payment_voucher_filter_account_id" style="width: 100%">
                <option selected="selected" value="">{{ __('accounting::lang.all') }}</option>
                @foreach ($accounts as $account)
                    @if ($account)
                        <option value="{{ $account['id'] }}">{{ $account['name'] }}</option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>
@endif
@if (empty($only) || in_array('payment_voucher_filter_payment_status', $only))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('payment_voucher_filter_payment_status', __('purchase.payment_status') . ':') !!}
            {!! Form::select(
                'payment_voucher_filter_payment_status',
                [
                    'paid' => __('lang_v1.paid'),
                    'due' => __('lang_v1.due'),
                    'partial' => __('lang_v1.partial'),
                    'overdue' => __('lang_v1.overdue'),
                ],
                null,
                ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')],
            ) !!}
        </div>
    </div>
@endif
