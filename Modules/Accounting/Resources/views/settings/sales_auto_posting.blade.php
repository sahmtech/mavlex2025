@extends('layouts.app')

@section('title', __('accounting::lang.configure_sales_auto_posting'))

@section('content')
    <section class="content-header">
        <h1>@lang('accounting::lang.configure_sales_auto_posting')</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="callout callout-info">
                    <p class="mb-0">@lang('accounting::lang.configure_sales_auto_posting_help')</p>
                </div>

                {!! Form::open([
                    'route' => 'accounting.sales-auto-posting.save',
                    'method' => 'post',
                ]) !!}

                @foreach ($business_locations as $business_location)
                    @php
                        $default_map = json_decode($business_location->accounting_default_map, true) ?: [];
                        $sale_payment_account = isset($default_map['sale']['payment_account'])
                            ? \Modules\Accounting\Entities\AccountingAccount::find($default_map['sale']['payment_account'])
                            : null;
                        $sale_deposit_to = isset($default_map['sale']['deposit_to'])
                            ? \Modules\Accounting\Entities\AccountingAccount::find($default_map['sale']['deposit_to'])
                            : null;
                        $sales_payments_payment_account = isset($default_map['sell_payment']['payment_account'])
                            ? \Modules\Accounting\Entities\AccountingAccount::find($default_map['sell_payment']['payment_account'])
                            : null;
                        $sales_payments_deposit_to = isset($default_map['sell_payment']['deposit_to'])
                            ? \Modules\Accounting\Entities\AccountingAccount::find($default_map['sell_payment']['deposit_to'])
                            : null;
                    @endphp

                    @component('components.widget', ['title' => $business_location->name])
                        <strong>@lang('sale.sale')</strong>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('payment_account_sale_' . $business_location->id, __('accounting::lang.payment_account') . ':') !!}
                                    {!! Form::select(
                                        'payment_account',
                                        !is_null($sale_payment_account) ? [$sale_payment_account->id => $sale_payment_account->name] : [],
                                        $sale_payment_account->id ?? null,
                                        [
                                            'class' => 'form-control accounts-dropdown width-100',
                                            'placeholder' => __('accounting::lang.payment_account'),
                                            'name' => "accounting_default_map[$business_location->id][sale][payment_account]",
                                            'id' => $business_location->id . 'sale_payment_account',
                                        ],
                                    ) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('deposit_to_sale_' . $business_location->id, __('accounting::lang.deposit_to') . ':') !!}
                                    {!! Form::select(
                                        'deposit_to',
                                        !is_null($sale_deposit_to) ? [$sale_deposit_to->id => $sale_deposit_to->name] : [],
                                        $sale_deposit_to->id ?? null,
                                        [
                                            'class' => 'form-control accounts-dropdown width-100',
                                            'placeholder' => __('accounting::lang.deposit_to'),
                                            'name' => "accounting_default_map[$business_location->id][sale][deposit_to]",
                                            'id' => $business_location->id . '_sale_deposit_to',
                                        ],
                                    ) !!}
                                </div>
                            </div>
                        </div>

                        <hr>

                        <strong>@lang('accounting::lang.sales_payments')</strong>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('payment_account_sp_' . $business_location->id, __('accounting::lang.payment_account') . ':') !!}
                                    {!! Form::select(
                                        'payment_account',
                                        !is_null($sales_payments_payment_account)
                                            ? [$sales_payments_payment_account->id => $sales_payments_payment_account->name]
                                            : [],
                                        $sales_payments_payment_account->id ?? null,
                                        [
                                            'class' => 'form-control accounts-dropdown width-100',
                                            'placeholder' => __('accounting::lang.payment_account'),
                                            'name' => "accounting_default_map[$business_location->id][sell_payment][payment_account]",
                                            'id' => $business_location->id . 'sales_payments_payment_account',
                                        ],
                                    ) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('deposit_to_sp_' . $business_location->id, __('accounting::lang.deposit_to') . ':') !!}
                                    {!! Form::select(
                                        'deposit_to',
                                        !is_null($sales_payments_deposit_to)
                                            ? [$sales_payments_deposit_to->id => $sales_payments_deposit_to->name]
                                            : [],
                                        $sales_payments_deposit_to->id ?? null,
                                        [
                                            'class' => 'form-control accounts-dropdown width-100',
                                            'placeholder' => __('accounting::lang.deposit_to'),
                                            'name' => "accounting_default_map[$business_location->id][sell_payment][deposit_to]",
                                            'id' => $business_location->id . 'sales_payments_deposit_to',
                                        ],
                                    ) !!}
                                </div>
                            </div>
                        </div>
                    @endcomponent
                @endforeach

                @if ($business_locations->isEmpty())
                    <div class="alert alert-warning">@lang('accounting::lang.configure_sales_auto_posting_no_locations')</div>
                @else
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                {{ Form::submit(__('messages.update'), ['class' => 'btn btn-primary btn-big']) }}
                            </div>
                        </div>
                    </div>
                @endif

                {!! Form::close() !!}
            </div>
        </div>
    </section>
@stop

@section('javascript')
    @include('accounting::accounting.common_js')
@endsection
