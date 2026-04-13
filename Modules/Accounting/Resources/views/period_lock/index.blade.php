@extends('layouts.app')

@section('title', __('accounting::lang.period_locks'))

@section('content')
    <section class="content-header">
        <h1>@lang('accounting::lang.period_locks')</h1>
    </section>
    <section class="content">
        @component('components.widget', ['class' => 'box-primary'])
            <p class="help-block">@lang('accounting::lang.period_locks_help')</p>
            {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\PeriodLockController::class, 'store']), 'method' => 'post', 'class' => 'form-inline']) !!}
            <div class="form-group">
                {!! Form::label('lock_year', __('accounting::lang.year') . ':') !!}
                {!! Form::number('lock_year', date('Y'), ['class' => 'form-control', 'required', 'min' => 2000, 'max' => 2100]) !!}
            </div>
            <div class="form-group" style="margin-left:10px;">
                {!! Form::label('lock_month', __('accounting::lang.month') . ':') !!}
                {!! Form::number('lock_month', date('n'), ['class' => 'form-control', 'required', 'min' => 1, 'max' => 12]) !!}
            </div>
            <button type="submit" class="btn btn-primary" style="margin-left:10px;">@lang('messages.add')</button>
            {!! Form::close() !!}
        @endcomponent

        @component('components.widget', ['class' => 'box-solid'])
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.year')</th>
                        <th>@lang('accounting::lang.month')</th>
                        <th>@lang('messages.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locks as $lock)
                        <tr>
                            <td>{{ $lock->lock_year }}</td>
                            <td>{{ $lock->lock_month }}</td>
                            <td>
                                {!! Form::open(['url' => action([\Modules\Accounting\Http\Controllers\PeriodLockController::class, 'destroy'], $lock->id), 'method' => 'delete', 'style' => 'display:inline']) !!}
                                <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('@lang('accounting::lang.confirm_unlock_period')')">@lang('messages.delete')</button>
                                {!! Form::close() !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">@lang('accounting::lang.no_period_locks')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $locks->links() }}
        @endcomponent
    </section>
@endsection
