@extends('layouts.auth2')

@section('title', __('lang_v1.reset_password'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3" style="padding-top: 31px;">
            <div class="glass-container tw-p-8 md:tw-p-10" style="padding: 31px;">
                
                <div class="tw-text-center tw-mb-4">
                    <img src="{{ asset('img/logo-small.png')}}" alt="logo" style="height: 40px; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2)); margin-bottom: 15px;justify-self: center;">
                    <h1 class="tw-text-2xl tw-font-bold text-white">@lang('lang_v1.reset_password')</h1>
                    <h2 class="tw-text-sm tw-text-white opacity-80">@lang('lang_v1.send_password_reset_link')</h2>
                </div>

                @if (session('status') && is_string(session('status')))
                    <div class="alert alert-info tw-rounded-lg tw-border-none tw-bg-blue-500 tw-text-white" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" style="padding: 1rem 2rem 2rem 2rem">
                    {{ csrf_field() }}
                    
                    <div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
                        <label class="label">@lang('business.email')</label>
                        <input id="email" type="email" class="form-control tw-h-12 tw-rounded-lg" name="email" value="{{ old('email') }}" required autofocus placeholder="@lang('lang_v1.email_address')">

                        @if ($errors->has('email'))
                            <span class="help-block">
                                <strong>{{ $errors->first('email') }}</strong>
                            </span>
                        @endif
                    </div>

                    <button type="submit" class="tw-w-full tw-h-12 tw-bg-blue-600 tw-text-white tw-rounded-xl tw-font-bold hover:tw-bg-blue-700 tw-transition-all tw-mt-4" style="background: #462e67;">
                        @lang('lang_v1.send_password_reset_link')
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

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.change_lang').click(function() {
                window.location = "{{ route('password.request') }}?lang=" + $(this).attr('value');
            });
        })
    </script>
@endsection