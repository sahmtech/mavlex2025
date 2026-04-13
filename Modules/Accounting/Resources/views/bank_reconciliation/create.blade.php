@extends('layouts.app')

@section('title', __('accounting::lang.bank_reconciliation'))

@section('content')
    <section class="content-header">
        <h1>@lang('accounting::lang.bank_reconciliation')</h1>
    </section>
    <section class="content">
        @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'store']), 'method' => 'post']) !!}
            <div class="form-group">
                {!! Form::label('accounting_account_id', __('accounting::lang.bank_gl_account') . ':*') !!}
                {!! Form::select('accounting_account_id', $accounts, null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%;']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('statement_date', __('accounting::lang.statement_date') . ':*') !!}
                {!! Form::date('statement_date', null, ['class' => 'form-control', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('statement_balance', __('accounting::lang.statement_balance') . ':*') !!}
                {!! Form::text('statement_balance', null, ['class' => 'form-control input_number', 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('notes', __('accounting::lang.additional_notes') . ':') !!}
                {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <a href="{{ action([\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'index']) }}" class="btn btn-default">@lang('messages.cancel')</a>
            {!! Form::close() !!}
        @endcomponent
    </section>
@endsection
