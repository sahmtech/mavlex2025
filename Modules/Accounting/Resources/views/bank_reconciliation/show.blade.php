@extends('layouts.app')

@section('title', __('accounting::lang.bank_reconciliation'))

@section('content')
    <section class="content-header">
        <h1>@lang('accounting::lang.bank_reconciliation') #{{ $recon->id }}</h1>
    </section>
    <section class="content">
        <div class="box box-solid">
            <div class="box-body">
                <p><strong>@lang('accounting::lang.statement_date'):</strong> {{ $recon->statement_date }}</p>
                <p><strong>@lang('accounting::lang.statement_balance'):</strong> @format_currency($recon->statement_balance)</p>
                <p><strong>@lang('accounting::lang.book_balance'):</strong> @format_currency($recon->book_balance)</p>
                <p><strong>@lang('accounting::lang.cleared_balance_delta'):</strong> @format_currency($clearedSum)</p>
            </div>
        </div>

        @if ($recon->status === 'open')
            {!! Form::open(['url' => route('accounting.bank-reconciliation.items', $recon->id), 'method' => 'post']) !!}
            @component('components.widget', ['class' => 'box-primary'])
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>@lang('accounting::lang.cleared')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('accounting::lang.debit')</th>
                            <th>@lang('accounting::lang.credit')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php $line = $item->glLine; @endphp
                            @if ($line)
                                <tr>
                                    <td>
                                        {!! Form::checkbox('cleared[]', $item->id, $item->is_cleared) !!}
                                    </td>
                                    <td>{{ $line->operation_date }}</td>
                                    <td>@if ($line->type === 'debit') @format_currency($line->amount) @else — @endif</td>
                                    <td>@if ($line->type === 'credit') @format_currency($line->amount) @else — @endif</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            @endcomponent
            {!! Form::close() !!}

            {!! Form::open(['url' => route('accounting.bank-reconciliation.complete', $recon->id), 'method' => 'post', 'style' => 'display:inline']) !!}
            <button type="submit" class="btn btn-success">@lang('accounting::lang.mark_reconciled')</button>
            {!! Form::close() !!}
        @endif

        <a href="{{ action([\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'index']) }}" class="btn btn-default">@lang('messages.cancel')</a>
    </section>
@endsection
