@extends('layouts.dashboard-auth')

@section('title', __('auth.login'))

@section('content')
<style>
    .signin-wrapper{
        display:flex;
        justify-content:center;
        align-items:flex-start;
        min-height:calc(100vh - 100px);
        padding:0 20px 40px 20px;
        font-family: 'Almarai', sans-serif important;
        background-color:white;
        margin:0;
        padding-top:100px;
        padding-bottom:40px;
        direction:{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};
        text-align:{{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
    }
    .signin-box{
        background:#F8F8F8;
        padding:40px 30px;
        width:100%;
        max-width: 556px;
        height: 404px;
        position:relative;
        z-index:10;
    }
    #mobile{
        width:100%;
        border:1px solid #ddd;
        border-radius:6px;
        padding:10px 14px;
        font-size:14px;
        outline:none;
        box-sizing:border-box;
        font-style:italic !important;
        transition:border-color 0.3s,box-shadow 0.3s;
        text-align:{{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
    }
    .h-10{
        height: 0;
    }
</style>
<div class="signin-wrapper">
    <div class="signin-box">
        <h2 style="text-align:center;margin-bottom:20px;font-size:24px;font-weight:bold;">{{ __('auth.login') }}</h2>

        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('signin.verify') }}" style="display:flex;flex-direction:column;gap: 20px;">
            @csrf
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">

            <!-- Mobile -->
            <div style="margin-bottom:15px;">
                <input id="mobile" type="text" name="mobile"
                    value="{{ old('mobile') }}" required
                    placeholder="{{ __('auth.mobile') }}">
            </div>

            <div style="display:flex;flex-direction:{{ app()->getLocale() == 'ar' ?  'row' : 'row-reverse' }};align-items:center;margin-bottom:15px;">
                <input id="remember_me" type="checkbox" name="remember" style="width:16px;height:16px;margin-right:6px;">
                <label for="remember_me" style="margin:0;font-size:14px;">{{ __('auth.remember_me') }}</label>
            </div>

            <!-- Submit -->
            <div style="margin-top:15px;display: flex;">
                <button type="submit" style="background: #CF9233;font-weight: bold;border: none;border-radius: 36px;padding: 15px 20px;width: 50%;margin: auto;font-size: 18px;cursor: pointer;transition: background 0.3s, transform 0.2s;display: inline-block;text-align: center;color: rgb(255, 255, 255) !important;transform: none;" onmouseover="this.style.transform='translateY(-2px)';"
                   onmouseout="this.style.transform='none';">
                    {{ __('auth.login_button') }}
                </button>
            </div>
        </form>

        <p style="text-align:center;margin-top:20px;">
            {{ __('auth.no_account') }}
            <a href="{{ route('signup') }}" style="color:#CF9233;font-weight:500;text-decoration:none;">{{ __('auth.signup') }}</a>
        </p>
    </div>
</div>

@endsection
