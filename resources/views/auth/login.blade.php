@extends('layouts.auth2')
@section('title', __('lang_v1.login'))
@inject('request', 'Illuminate\Http\Request')

@section('content')
@php
    $username = old('username');
    $password = null;
    if (config('app.env') == 'demo') {
        $username = 'admin';
        $password = '123456';
        $demo_types = [
            'all_in_one' => 'admin', 'super_market' => 'admin', 'pharmacy' => 'admin-pharmacy',
            'electronics' => 'admin-electronics', 'services' => 'admin-services',
            'restaurant' => 'admin-restaurant', 'superadmin' => 'superadmin',
            'woocommerce' => 'woocommerce_user', 'essentials' => 'admin-essentials',
            'manufacturing' => 'manufacturer-demo',
        ];
        if (!empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types)) {
            $username = $demo_types[$_GET['demo_type']];
        }
    }
@endphp

<div class="container">
    <div class="row">
        {{-- قسم الـ Demo --}}
        @if (config('app.env') == 'demo')
            <div class="col-md-5">
                <div class="glass-container tw-p-6 tw-mb-4" style="max-height: 80vh; overflow-y: auto;">
                    <h4 class="text-center tw-text-white">Demo Shops</h4>
                    <div class="tw-grid tw-grid-cols-2 tw-gap-2">
                        @foreach($demo_types as $type => $admin)
                            <a href="?demo_type={{$type}}" class="btn btn-sm btn-default demo-login" data-admin="{{$admin}}">{{ucwords(str_replace('_',' ',$type))}}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

         <div class="{{ config('app.env') == 'demo' ? 'col-md-7' : 'col-md-6 col-md-offset-3' }}" style="padding-top: 31px;">
            <div class="glass-container tw-p-8 md:tw-p-10" style="padding-top: 31px;">
                
                {{-- إضافة اللوغو داخل الفورم بشكل جميل --}}
                <div class="tw-text-center tw-mb-4">
                    <img src="{{ asset('img/logo-small.png')}}" alt="logo" style="height: 40px; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2)); margin-bottom: 15px;justify-self: center;">
                    <h1 class="tw-text-2xl tw-font-bold">@lang('lang_v1.welcome_back')</h1>
                    <h2 class="tw-text-sm opacity-80">@lang('lang_v1.login_to_your') {{ config('app.name') }}</h2>
                </div>

                <form method="POST" action="{{ route('login') }}" id="login-form" style="padding: 2rem 4rem 4rem 4rem">
                    {{ csrf_field() }}
                    
                    <div class="form-group {{ $errors->has('username') ? ' has-error' : '' }}">
                        <label  style="color: #f0e6ff !important;">@lang('lang_v1.username')</label>
                        <input class="form-control tw-h-12 tw-rounded-lg" name="username" value="{{ $username }}" required autofocus id="username">
                        @if ($errors->has('username'))
                            <span class="help-block"><strong>{{ $errors->first('username') }}</strong></span>
                        @endif
                    </div>

                    <div class="form-group {{ $errors->has('password') ? ' has-error' : '' }}" style="position: relative;">
                        <div class="tw-flex tw-justify-between">
                            <label style="color: #f0e6ff !important;">@lang('lang_v1.password')</label>
                            @if (config('app.env') != 'demo')
                                <a href="{{ route('password.request') }}" class="tw-text-xs tw-text-white hover:tw-underline">@lang('lang_v1.forgot_your_password')</a>
                            @endif
                        </div>
                        <input type="password" name="password" id="password" class="form-control tw-h-12 tw-rounded-lg" value="{{ $password }}" required>
                        <button type="button" id="show_hide_icon" style="position: absolute; right: 10px; top: 35px; background: none; border: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tw-w-6" fill="none" viewBox="0 0 24 24" stroke="black"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </button>
                    </div>

                    <div class="tw-mb-4">
                        <label class="tw-flex tw-items-center tw-gap-2"  style="color: #f0e6ff !important;">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>@lang('lang_v1.remember_me')</span>
                        </label>
                    </div>

                    <button type="submit" class="tw-w-full tw-h-12 tw-bg-blue-600 tw-text-white tw-rounded-xl tw-font-bold hover:tw-bg-blue-700 tw-transition-all" style="background: #462e67;">
                        @lang('lang_v1.login')
                    </button>
                </form>

                @if (config('constants.allow_registration'))
                    <div class="tw-text-center tw-mt-6">
                        <span class="opacity-80">{{ __('business.not_yet_registered') }}</span>
                        <a href="{{ route('business.getRegister') }}" class="tw-text-white tw-font-bold hover:tw-underline">{{ __('business.register_now') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('.change_lang').click(function() {
            window.location = "{{ route('login') }}?lang=" + $(this).attr('value');
        });
        $('a.demo-login').click(function(e) {
            e.preventDefault();
            $('#username').val($(this).data('admin'));
            $('#password').val("{{ $password }}");
            $('form#login-form').submit();
        });
        $('#show_hide_icon').on('click', function() {
            const pwd = $('#password');
            const type = pwd.attr('type') === 'password' ? 'text' : 'password';
            pwd.attr('type', type);
        });
    });
</script>
@endsection