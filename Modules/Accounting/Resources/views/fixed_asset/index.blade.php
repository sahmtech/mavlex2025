@extends('layouts.app')

@section('title', __('accounting::lang.fixed_assets_module'))

@section('content')
    <section class="content-header">
        <h1>@lang('accounting::lang.fixed_assets_module')</h1>
    </section>
    <section class="content">
        <div class="box-tools" style="margin-bottom:10px;">
            <a class="btn btn-primary" href="{{ action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'create']) }}">
                <i class="fas fa-plus"></i> @lang('messages.add')
            </a>
        </div>

        @component('components.widget', ['class' => 'box-solid'])
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>@lang('user.name')</th>
                        <th>@lang('accounting::lang.acquisition_date')</th>
                        <th>@lang('accounting::lang.cost')</th>
                        <th>@lang('accounting::lang.useful_life_months')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('accounting::lang.post_depreciation')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr>
                            <td>{{ $asset->name }}</td>
                            <td>{{ $asset->acquisition_date }}</td>
                            <td>@format_currency($asset->cost)</td>
                            <td>{{ $asset->useful_life_months }}</td>
                            <td>{{ $asset->status }}</td>
                            <td>
                                {!! Form::open(['url' => route('accounting.fixed-assets.depreciate', $asset->id), 'method' => 'post', 'class' => 'form-inline']) !!}
                                <input type="number" name="period_year" class="form-control" value="{{ date('Y') }}" min="2000" max="2100" style="width:90px;display:inline-block;">
                                <input type="number" name="period_month" class="form-control" value="{{ date('n') }}" min="1" max="12" style="width:70px;display:inline-block;">
                                <button type="submit" class="btn btn-xs btn-primary">@lang('accounting::lang.post_depreciation')</button>
                                {!! Form::close() !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">@lang('accounting::lang.no_fixed_assets')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $assets->links() }}
        @endcomponent
    </section>
@endsection
