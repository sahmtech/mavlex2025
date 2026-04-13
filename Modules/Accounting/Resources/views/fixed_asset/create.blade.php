@extends('layouts.app')

@section('title', __('accounting::lang.fixed_assets_module'))

@section('content')
    <section class="content-header">
        <h1>@lang('accounting::lang.fixed_assets_module')</h1>
    </section>
    <section class="content">
        @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'store']), 'method' => 'post']) !!}
            <div class="form-group">
                {!! Form::label('name', __('user.name') . ':*') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('acquisition_date', __('accounting::lang.acquisition_date') . ':*') !!}
                {!! Form::date('acquisition_date', null, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('cost', __('accounting::lang.cost') . ':*') !!}
                {!! Form::text('cost', null, ['class' => 'form-control input_number', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('salvage_value', __('accounting::lang.salvage_value') . ':') !!}
                {!! Form::text('salvage_value', 0, ['class' => 'form-control input_number']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('useful_life_months', __('accounting::lang.useful_life_months') . ':*') !!}
                {!! Form::number('useful_life_months', null, ['class' => 'form-control', 'required', 'min' => 1]) !!}
            </div>
            <div class="form-group">
                {!! Form::label('asset_account_id', __('accounting::lang.asset_account') . ':*') !!}
                {!! Form::select('asset_account_id', $accounts, null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%;']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('accumulated_depreciation_account_id', __('accounting::lang.accumulated_depreciation_account') . ':*') !!}
                {!! Form::select('accumulated_depreciation_account_id', $accounts, null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%;']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('depreciation_expense_account_id', __('accounting::lang.depreciation_expense_account') . ':*') !!}
                {!! Form::select('depreciation_expense_account_id', $accounts, null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%;']) !!}
            </div>
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <a href="{{ action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index']) }}" class="btn btn-default">@lang('messages.cancel')</a>
            {!! Form::close() !!}
        @endcomponent
    </section>
@endsection
