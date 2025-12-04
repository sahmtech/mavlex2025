@extends('layouts.app')
@section('title', __('zatcaintegrationksa::lang.zatca'))
@section('content')
    @include('zatcaintegrationksa::layouts.nav')

    <section class="content no-print">
        <div class="row">
            <!-- Total Invoices -->
            <div class="col-md-3 col-sm-6 col-xs-12">
                @component('components.static', [
                    'svg_bg' => 'tw-bg-blue-400',
                    'svg_text' => 'tw-text-white',
                    'svg' =>
                        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-text"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M9 12h6M9 16h6M9 20h6"/></svg>',
                ])
                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                        {{ __('zatcaintegrationksa::lang.total_invoices') }}
                    </p>
                    <p class="tw-text-xl tw-font-semibold tw-text-gray-900">
                        {{ $total_un_synced + $total_synced }}
                    </p>
                @endcomponent
            </div>

            <!-- Un-synced Transactions -->
            <div class="col-md-3 col-sm-6 col-xs-12">
                @component('components.static', [
                    'svg_bg' => 'tw-bg-yellow-400',
                    'svg_text' => 'tw-text-white',
                    'svg' =>
                        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-upload"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3v12" /><path d="M16 7l-4 -4l-4 4" /><path d="M4 16v4h16v-4" /></svg>',
                ])
                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                        {{ __('zatcaintegrationksa::lang.total_un_synced') }}
                    </p>
                    <p class="tw-text-xl tw-font-semibold tw-text-gray-900">
                        {{ $total_un_synced }}
                    </p>
                @endcomponent
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                @component('components.static', [
                    'svg_bg' => 'tw-bg-orange-400',
                    'svg_text' => 'tw-text-white',
                    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" stroke-width="2">
                                                  <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"></path>
                                                  <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"></path>
                                                  <path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"></path>
                                                  <path d="M12 17v1m0 -8v1"></path>
                                                </svg>',
                ])
                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                        {{ __('zatcaintegrationksa::lang.total_synced') }}
                    </p>
                    <p class="tw-text-xl tw-font-semibold tw-text-gray-900">
                        {{ $total_synced }}
                    </p>
                @endcomponent
            </div>

            <!-- Successfully Synced Transactions -->
            <div class="col-md-3 col-sm-6 col-xs-12">
                @component('components.static', [
                    'svg_bg' => 'tw-bg-green-400',
                    'svg_text' => 'tw-text-white',
                    'svg' =>
                        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-checks"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 12l5 5l10 -10" /><path d="M2 12l5 5m0 -5l5 -5" /></svg>',
                ])
                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                        {{ __('zatcaintegrationksa::lang.total_success_synced') }}
                    </p>
                    <p class="tw-text-xl tw-font-semibold tw-text-gray-900">
                        {{ $total_success_synced }}
                    </p>
                @endcomponent
            </div>

            <!-- Failed Transactions -->
            <div class="col-md-3 col-sm-6 col-xs-12">
                @component('components.static', [
                    'svg_bg' => 'tw-bg-red-400',
                    'svg_text' => 'tw-text-white',
                    'svg' =>
                        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>',
                ])
                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                        {{ __('zatcaintegrationksa::lang.total_failed_synced') }}
                    </p>
                    <p class="tw-text-xl tw-font-semibold tw-text-gray-900">
                        {{ $total_failed_synced }}
                    </p>
                @endcomponent
            </div>
        </div>

        <div class="col-xs-12">
            <p class="help-block"><i>{!! __('zatcaintegrationksa::lang.version_info', ['version' => $module_version]) !!}</i></p>
        </div>
    </section>

@endsection
