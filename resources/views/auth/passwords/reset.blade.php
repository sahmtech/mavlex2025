@extends('layouts.auth2')

@section('title', __('lang_v1.reset_password'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3" style="padding-top: 31px;">
            <div class="glass-container tw-p-8 md:tw-p-10">
                
                {{-- إضافة اللوغو في الأعلى ليتناسب مع تصميم الدخول --}}
                <div class="tw-text-center tw-mb-4">
                    <img src="{{ asset('img/logo-small.png')}}" alt="logo" style="height: 65px; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2)); margin-bottom: 15px;">
                    <h1 class="tw-text-2xl tw-font-bold text-white">@lang('lang_v1.reset_password')</h1>
                </div>

                <form method="POST" action="{{ route('password.request') }}" style="padding: 1rem 2rem 2rem 2rem">
                    {{ csrf_field() }}

                    <input type="hidden" name="token" value="{{ $token }}">

                    {{-- حقل البريد الإلكتروني --}}
                    <div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
                        <label class="label">@lang('business.email')</label>
                        <input id="email" type="email" class="form-control tw-h-12 tw-rounded-lg" name="email"
                            value="{{ $email ?? old('email') }}" required autofocus placeholder="@lang('lang_v1.email_address')">

                        @if ($errors->has('email'))
                            <span class="help-block">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                        @endif
                    </div>

                    {{-- حقل كلمة المرور الجديدة --}}
                    <div class="form-group {{ $errors->has('password') ? ' has-error' : '' }}">
                        <label class="label">@lang('lang_v1.password')</label>
                        <input id="password" type="password" class="form-control tw-h-12 tw-rounded-lg" name="password"
                            required placeholder="@lang('lang_v1.password')">
                        
                        @if ($errors->has('password'))
                            <span class="help-block">
                                <strong>{{ $errors->first('password') }}</strong>
                            </span>
                        @endif
                    </div>

                    {{-- حقل تأكيد كلمة المرور --}}
                    <div class="form-group {{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                        <label class="label">@lang('business.confirm_password')</label>
                        <input id="password-confirm" type="password" class="form-control tw-h-12 tw-rounded-lg"
                            name="password_confirmation" required placeholder="@lang('business.confirm_password')">
                        
                        @if ($errors->has('password_confirmation'))
                            <span class="help-block">
                                <strong>{{ $errors->first('password_confirmation') }}</strong>
                            </span>
                        @endif
                    </div>

                    {{-- زر الإرسال بنفس ستايل تسجيل الدخول --}}
                    <button type="submit" class="tw-w-full tw-h-12 tw-bg-blue-600 tw-text-white tw-rounded-xl tw-font-bold hover:tw-bg-blue-700 tw-transition-all tw-mt-4">
                        @lang('lang_v1.reset_password')
                    </button>
                </form>

                <div class="tw-text-center tw-mt-6">
                    <a href="{{ route('login') }}" class="tw-text-white tw-font-bold hover:tw-underline">
                        <i class="fa fa-arrow-left"></i> @lang('lang_v1.back_to_login')
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection