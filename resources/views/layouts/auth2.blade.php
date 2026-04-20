<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name', 'POS') }}</title>

     @include('layouts.partials.css')
    @include('layouts.partials.extracss_auth')
    
     <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Cairo', sans-serif !important;
        }

        body {
            background: url("{{ asset('img/mavlex-background.jpg.jpeg') }}") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

         body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.5), rgba(0, 32, 91, 0.4));
            z-index: 0;
        }

        .container-fluid { 
            position: relative; 
            z-index: 10; 
            width: 100%; 
            padding-top: 80px; 
        }
        
        .auth-header {
            position: absolute;
            top: 0; left: 0; right: 0;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

         .glass-container {
            background: rgba(255, 255, 255, 0.12) !important;
            backdrop-filter: blur(15px) saturate(150%);
            -webkit-backdrop-filter: blur(15px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3) !important;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.95) !important;
            border: none !important;
            color: #1a1a1a !important;
            font-weight: 600 !important;
        }

        .btn-register-top {
            text-decoration: none !important;
            border: 2px solid #ffffff;
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-register-top:hover {
            background: white;
            color: #d9e1e8;
        }

         .demo-section::-webkit-scrollbar {
            width: 5px;
        }
        .demo-section::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
        }
       .label {
  
    color: #f0e6ff !important;
}
        .tw-text-sm {
            color: #e0d6ed;
        }
    </style>
</head>

<body>
    @inject('request', 'Illuminate\Http\Request')

    <header class="auth-header">
        <div class="language-wrapper">
             @include('layouts.partials.language_btn')
        </div>

        <div class="tw-flex tw-items-center tw-gap-4">
            @if (config('constants.allow_registration'))
                <a href="{{ route('business.getRegister') }}" class="btn-register-top">
                    {{ __('business.register') }}
                </a>
            @endif
        </div>
    </header>

    <div class="container-fluid">
        @yield('content')
    </div>

    @include('layouts.partials.javascripts')
    
    <script src="{{ asset('js/login.js?v=' . ($asset_v ?? '1.0')) }}"></script>
    
    @yield('javascript')
</body>
</html>