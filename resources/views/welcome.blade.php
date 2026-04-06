@extends('layouts.auth2')
@section('title', config('app.name', 'ultimatePOS'))
@inject('request', 'Illuminate\Http\Request')

@section('content')
<div class="welcome-container">
    <div class="content-wrapper">
        <h1 class="brand-name">
            {{ config('app.name', 'UltimatePOS') }}
        </h1>
        
        <p class="brand-slogan">
            {{ env('APP_TITLE', 'إدارة أعمالك بكل احترافية') }}
        </p>

        <div class="action-buttons">
            <a href="{{ action([\App\Http\Controllers\Auth\LoginController::class, 'login']) }}" class="btn-main">
                @lang('business.sign_in')
            </a>
            @if (config('constants.allow_registration'))
            <a href="{{ route('business.getRegister') }}" class="btn-secondary">
                @lang('business.register')
            </a>
            @endif
        </div>
    </div>
</div>

<style>
    .welcome-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh; 
        text-align: center;
        padding: 20px;
    }

    .content-wrapper {
        background: rgba(255, 255, 255, 0.15); 
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        padding: 60px 40px; 
        border-radius: 40px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        max-width: 800px;
        width: 100%;
        height: auto; 
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    }

    .brand-name {
        font-size: clamp(2.5rem, 8vw, 4.5rem); 
        font-weight: 900;
        color: #ffffff;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 3px;
        line-height: 1.1;
        text-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }

    .brand-slogan {
        font-size: clamp(1rem, 3vw, 1.4rem);
        color: rgba(255, 255, 255, 0.9);
        margin-top: 25px;
        font-weight: 400;
        letter-spacing: 1px;
    }

    .action-buttons {
        margin-top: 50px;
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-main, .btn-secondary {
        padding: 16px 45px;
        border-radius: 50px;
        font-weight: 700;
        text-decoration: none !important;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-size: 1.1rem;
        display: inline-block;
    }

    .btn-main {
        background: #ffffff;
        color: #1a3a8a;
        box-shadow: 0 10px 20px rgba(255,255,255,0.1);
    }
    .btn-main:hover { 
        background: #f8f9fa; 
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(255,255,255,0.2);
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border: 2px solid rgba(255, 255, 255, 0.8);
    }
    .btn-secondary:hover { 
        background: #ffffff;
        color: #1a3a8a;
        transform: translateY(-5px);
    }

    @media (max-width: 768px) {
        .content-wrapper { 
            padding: 40px 20px; 
            border-radius: 30px;
        }
        .action-buttons {
            flex-direction: column;
            gap: 15px;
        }
        .btn-main, .btn-secondary {
            width: 100%;
        }
    }
</style>
@endsection