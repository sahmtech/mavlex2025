@extends('layouts.app')
@section('title', __('zatcaintegrationksa::lang.on_boarding'))
@section('content')
    @include('zatcaintegrationksa::layouts.nav')

    <section class="content">
        <div class="row">
            @php
                $settings = json_decode($business->zatca_settings, true);

                $pos_settings = json_decode($business->pos_settings, true);

            @endphp

            <div class="col-md-4">

                @component('components.widget', ['class' => 'box-primary'])
                    <form
                        action="{{ action([\Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'posBussinesUpdate']) }}"
                        method="POST">
                        @csrf
                        <div class="form-group d-flex align-items-center">
                            <span class="check-icon">
                                @if (!empty($pos_settings['disable_discount']))
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <i class="fas fa-times-circle text-danger"></i>
                                @endif
                            </span>
                            <label class="ms-2">{{ __('lang_v1.disable_discount') }}</label>
                        </div>

                        <div class="form-group d-flex align-items-center">
                            <span class="check-icon">
                                @if (!empty($pos_settings['disable_order_tax']))
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <i class="fas fa-times-circle text-danger"></i>
                                @endif
                            </span>
                            <label class="ms-2">{{ __('lang_v1.disable_order_tax') }}</label>
                        </div>

                        <div class="form-group d-flex align-items-center">
                            <div class="form-group">
                                @if ($business->default_sales_discount == 0)
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <i class="fas fa-times-circle text-danger"></i>
                                @endif
                                {!! Form::label('default_sales_discount', __('zatcaintegrationksa::lang.set_default_sales_discount', ['discount' => ( @num_format($business->default_sales_discount) . '%')])) !!}                            </div>
                        </div>

                        <p class="m-5">@lang('zatcaintegrationksa::lang.sync_warning')</p>
                        <div class="form-group">
                            {!! Form::submit(__('zatcaintegrationksa::lang.apply_setting'), [
                                'class' => 'tw-dw-btn tw-dw-btn-success tw-text-white tw-dw-btn-lg',
                            ]) !!}
                        </div>
                    </form>
                @endcomponent
            </div>
            <div class="col-md-4">


                @component('components.widget', ['class' => 'box-primary'])
                    <form
                        action="{{ action([\Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'zataSetting']) }}"
                        method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="sync_frequency">{{ __('zatcaintegrationksa::lang.auto_sync') }}</label>
                            {!! Form::select(
                                'sync_frequency',
                                [
                                    'disable' => __('zatcaintegrationksa::lang.disable'),
                                    'instant' => __('zatcaintegrationksa::lang.instant'),
                                    'daily' => __('zatcaintegrationksa::lang.daily'),
                                ],
                                $settings['sync_frequency'] ?? null,
                                ['class' => 'form-control', 'required'],
                            ) !!}
                        </div>
                        <div class="mt-2">
                            <small class="form-text text-muted">
                                1. {{ __('zatcaintegrationksa::lang.disable') }} : {{ __('zatcaintegrationksa::lang.disable_description') }}
                            </small> <br>
                            <small class="form-text text-muted">
                                2. {{ __('zatcaintegrationksa::lang.instant') }} : {{ __('zatcaintegrationksa::lang.instant_description') }}
                            </small> <br>
                            <small class="form-text text-muted">
                                3. {{ __('zatcaintegrationksa::lang.daily') }} : {{ __('zatcaintegrationksa::lang.daily_description') }}
                            </small>
                        </div>
                        <br>
                        <div class="form-group mt-2">
                            {!! Form::submit(__('messages.save'), ['class' => 'tw-dw-btn tw-dw-btn-success tw-text-white tw-dw-btn-lg']) !!}
                        </div>
                    </form>
                @endcomponent
            </div>
            <div class="col-md-4">
                @component('components.widget', ['class' => 'box-primary'])
                    <div>
                        <h4 class="text-primary">@lang('zatcaintegrationksa::lang.developer_and_simulation_portal')</h4>
                        <div class="row mb-5">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-left">@lang('zatcaintegrationksa::lang.invoice_synced_developer_portal')</strong>
                                    <span style="margin: 10px">{{ $mode_count->developer_portal_count }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <strong>@lang('zatcaintegrationksa::lang.invoice_synced_simulation_portal')</strong>
                                    <span style="margin: 10px">{{ $mode_count->simulation_count }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-12 text-center">
                        <a href="{{ action([\Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'DeleteTestingInvoice']) }}"
                            class="tw-dw-btn tw-dw-btn-secondary btw-dw-btn-lg delete_zatca_invoice">@lang('zatcaintegrationksa::lang.unsync_invoice')</a>
                    </div>
                @endcomponent
            </div>
        </div>
        <!-- Custom Tabs -->


        @component('components.widget', ['class' => 'box-primary'])
            <div class="mb-5">
                <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('zatcaintegrationksa::lang.on_boarding')</h1>
                <p class="tw-text-sm tw-text-gray-600">@lang('zatcaintegrationksa::lang.onboarding_description')</p>
                <p class="tw-text-sm tw-text-gray-600">@lang('zatcaintegrationksa::lang.onboarding_instruction')</p>
                <p class="tw-text-sm tw-text-gray-600"> <strong>@lang('zatcaintegrationksa::lang.note')</strong> @lang('zatcaintegrationksa::lang.portal_mode_instruction')</p>
            </div>
            <div class="nav-tabs-custom mt-5">
                <ul class="nav nav-tabs">
                    @foreach ($business_locations as $index => $business_location)
                        <li class="@if ($index == 0) active @endif">
                            <a href="#cn_{{ $index }}" data-toggle="tab" aria-expanded="true">
                                {{ $business_location->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach ($business_locations as $index => $business_location)
                        @php
                            $details = json_decode($business_location->zatca_details, true);
                        @endphp

                        @php
                            $response = json_decode($business_location->zatca_response, true);
                        @endphp

                        <div class="tab-pane @if ($index == 0) active @endif" id="cn_{{ $index }}">
                            <div class="row">
                                <div class="row">
                                    <h2 class="tw-text-xl md:tw-text-2xl tw-font-bold tw-text-black col-md-3">
                                        {{ $business_location->name }}</h2>

                                    @if (isset($details['portal_mode']))
                                        <div
                                            class=" col-md-9 alert {{ $response['success'] ?? null ? 'alert-success' : 'alert-danger' }}">
                                            <span>{{ __('zatcaintegrationksa::lang.portal_mode') }} :</span>
                                            {{ $mode[$details['portal_mode'] ?? null] }},
                                            <span>{{ __('zatcaintegrationksa::lang.status') }} :</span>
                                            {{ $response['success'] ?? null ? 'Success' : 'Failed' }}
                                        </div>
                                    @else
                                        <div class=" col-md-9 alert alert-warning">
                                            {{ __('zatcaintegrationksa::lang.on_boading_in_complete') }}
                                        </div>
                                    @endif
                                </div>
                                {!! Form::open([
                                    'url' => action(
                                        [\Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'update'],
                                        $business_location->id,
                                    ),
                                    'method' => 'PUT',
                                    'id' => 'details_' . $business_location->id,
                                    'files' => true,
                                ]) !!}

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('portal_mode' . $index, __('zatcaintegrationksa::lang.portal_mode')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.portal_mode_instruction'))
                                            {!! Form::select('portal_mode', $mode, $details['portal_mode'] ?? 'core', [
                                                'class' => 'form-control',
                                                'required',
                                                'placeholder' => __('messages.please_select'),
                                                'id' => 'portal_mode' . $index,
                                            ]) !!}
                                            <button type="button" class="btn btn-info btn-sm mt-2 fill-test-data"
                                                data-index="{{ $index }}" style="display: none;">
                                                Fill Test Data
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('otp' . $index, __('zatcaintegrationksa::lang.otp')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.auth_otp_instruction'))
                                            {!! Form::number('otp', $details['otp'] ?? null, [
                                                'class' => 'form-control',
                                                'required',
                                                'placeholder' => __('zatcaintegrationksa::lang.otp'),
                                                'id' => 'OTP' . $index,
                                            ]) !!}

                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('email' . $index, __('business.email')) !!}
                                            {!! Form::email('email', $details['email'] ?? null, [
                                                'class' => 'form-control',
                                                'required',
                                                'placeholder' => __('business.email'),
                                                'id' => 'email' . $index,
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.email_help') !!}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('common_name' . $index, __('zatcaintegrationksa::lang.common_name')) !!}
                                            {!! Form::text('common_name', $details['common_name'] ?? $business_location->name, [
                                                'class' => 'form-control',
                                                'required',
                                                'placeholder' => __('zatcaintegrationksa::lang.common_name'),
                                                'id' => 'common_name' . $index,
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.common_name_help') !!}</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('country_code' . $index, __('zatcaintegrationksa::lang.country_code')) !!}
                                            {!! Form::text('country_code', 'SA', ['class' => 'form-control', 'readonly']) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('organization_unit_name' . $index, __('zatcaintegrationksa::lang.organization_unit_name')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.organization_unit_name_instruction'))
                                            {!! Form::text('organization_unit_name', $details['organization_unit_name'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.organization_unit_name'),
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.organization_unit_name_help') !!}</small>
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('organization_name' . $index, __('zatcaintegrationksa::lang.organization_name')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.organization_unit_name_instruction'))
                                            {!! Form::text('organization_name', $details['organization_name'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.organization_name'),
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.organization_name_help') !!}</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('egs_serial_number' . $index, __('zatcaintegrationksa::lang.egs_serial_number')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.egs_serial_number_instruction'))
                                            {!! Form::text('egs_serial_number', $details['egs_serial_number'] ?? '1-SDSA|2-FGDS|3-SDFG', [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.egs_serial_number'),
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('vat_number' . $index, __('zatcaintegrationksa::lang.vat_number')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.vat_number_instruction'))
                                            {!! Form::text('vat_number', $details['vat_number'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.vat_number'),
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.vat_number_help') !!}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('vat_name' . $index, __('zatcaintegrationksa::lang.vat_name')) !!}
                                            {!! Form::text('vat_name', $details['vat_name'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.vat_name'),
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.vat_name_help') !!}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('invoice_type' . $index, __('zatcaintegrationksa::lang.invoice_type')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.invoice_type_instruction'))
                                            {!! Form::select('invoice_type', $invoice_types, $details['invoice_type'] ?? null, ['class' => 'form-control']) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('registered_address' . $index, __('zatcaintegrationksa::lang.registered_address')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.registered_address_instruction'))
                                            {!! Form::text('registered_address', $details['registered_address'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.registered_address'),
                                            ]) !!}

                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('business_category' . $index, __('zatcaintegrationksa::lang.business_category')) !!}
                                            @show_tooltip(__('zatcaintegrationksa::lang.business_category_instruction'))
                                            {!! Form::text('business_category', $details['business_category'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.business_category'),
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.business_category_help') !!}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('crn' . $index, __('zatcaintegrationksa::lang.crn')) !!}
                                            {!! Form::text('crn', $details['crn'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.crn'),
                                            ]) !!}
                                            <small class="form-text text-muted">{!! __('zatcaintegrationksa::lang.crn_help') !!}</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('street_name' . $index, __('zatcaintegrationksa::lang.street_name')) !!}
                                            {!! Form::text('street_name', $details['street_name'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.street_name'),
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('building_number' . $index, __('zatcaintegrationksa::lang.building_number')) !!}
                                            {!! Form::text('building_number', $details['building_number'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.building_number'),
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>
                                <!-- Additional ZATCA Address Details -->


                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('plot_identification' . $index, __('zatcaintegrationksa::lang.plot_identification_secondary')) !!}
                                            {!! Form::text('plot_identification', $details['plot_identification'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.plot_identification'),
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('sub_division_name' . $index, __('zatcaintegrationksa::lang.sub_division_name_district')) !!}
                                            {!! Form::text('sub_division_name', $details['sub_division_name'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.sub_division_name'),
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('city_name' . $index, __('zatcaintegrationksa::lang.city_name')) !!}
                                            {!! Form::text('city_name', $details['city_name'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.city_name'),
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('postal_number' . $index, __('zatcaintegrationksa::lang.postal_number_zip')) !!}
                                            {!! Form::text('postal_number', $details['postal_number'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.postal_number'),
                                            ]) !!}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('country_name' . $index, __('zatcaintegrationksa::lang.country_name')) !!}
                                            {!! Form::text('country_name', $details['country_name'] ?? null, [
                                                'class' => 'form-control',
                                                'placeholder' => __('zatcaintegrationksa::lang.country_name'),
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        {!! Form::submit(__('messages.submit'), ['class' => 'tw-dw-btn tw-dw-btn-success tw-text-white tw-dw-btn-lg']) !!}
                                    </div>
                                </div>
                                {!! Form::close() !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endcomponent
    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            $(document).on('click', 'a.delete_zatca_invoice', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    text: "{{ __('zatcaintegrationksa::lang.delete_zatca_invoice_help') }} ",
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        window.location.href = $(this).attr('href');
                    }
                });
            });

            // Show/hide Fill Test Data button based on portal mode selection
            $('[id^="portal_mode"]').on('change', function() {
                const index = this.id.replace('portal_mode', '');
                const $fillButton = $(this).siblings('.fill-test-data');

                if ($(this).val() === 'developer-portal') {
                    $fillButton.show();
                } else {
                    $fillButton.hide();
                }
            });

            // Handle Fill Test Data button click
            $('.fill-test-data').on('click', function() {
                const $form = $(this).closest('form');

                // Fill the form fields with test data, scoped to the current form
                $form.find('input[name="otp"]').val('111222');
                $form.find('input[name="email"]').val('email@gmail.com');
                $form.find('input[name="common_name"]').val('TSTCO');
                $form.find('input[name="organization_unit_name"]').val('TSTCO-SA');
                $form.find('input[name="organization_name"]').val('TSTCO-SA');
                $form.find('input[name="egs_serial_number"]').val('1-SDSA|2-FGDS|3-SDFG');
                $form.find('input[name="vat_number"]').val('300000000000003');
                $form.find('input[name="vat_name"]').val('TSTCO VAT');
                $form.find('select[name="invoice_type"]').val('1100');
                $form.find('input[name="registered_address"]').val('RMRE1234');
                $form.find('input[name="business_category"]').val('Transportations');
                $form.find('input[name="crn"]').val('CRN123456');
                $form.find('input[name="street_name"]').val('Main Street');
                $form.find('input[name="building_number"]').val('123');
                $form.find('input[name="plot_identification"]').val('Plot567');
                $form.find('input[name="sub_division_name"]').val('Zone A');
                $form.find('input[name="city_name"]').val('Riyadh');
                $form.find('input[name="postal_number"]').val('11564');
                $form.find('input[name="country_name"]').val('Saudi Arabia');
            });
        });
    </script>
@endsection
