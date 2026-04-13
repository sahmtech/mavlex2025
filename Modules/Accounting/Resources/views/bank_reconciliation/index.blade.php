@extends('layouts.app')

@section('title', __('accounting::lang.bank_reconciliation'))

@section('content')
    <section class="content-header">
        <h1>@lang('accounting::lang.bank_reconciliation')</h1>
    </section>
    <section class="content">
        <div class="box-tools" style="margin-bottom:10px;">
            <a class="btn btn-primary" href="{{ action([\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'create']) }}">
                <i class="fas fa-plus"></i> @lang('messages.add')
            </a>
        </div>

        @component('components.widget', ['class' => 'box-solid'])
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('accounting::lang.account')</th>
                        <th>@lang('accounting::lang.statement_date')</th>
                        <th>@lang('accounting::lang.statement_balance')</th>
                        <th>@lang('accounting::lang.book_balance')</th>
                        <th>@lang('sale.status')</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ optional($row->account)->name }}</td>
                            <td>{{ $row->statement_date }}</td>
                            <td>@format_currency($row->statement_balance)</td>
                            <td>@format_currency($row->book_balance)</td>
                            <td>{{ $row->status }}</td>
                            <td>
                                <a href="{{ action([\Modules\Accounting\Http\Controllers\BankReconciliationController::class, 'show'], $row->id) }}" class="btn btn-xs btn-info">@lang('messages.view')</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">@lang('accounting::lang.no_bank_reconciliations')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        @endcomponent
    </section>
@endsection
