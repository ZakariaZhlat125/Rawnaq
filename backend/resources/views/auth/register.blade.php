@extends('layouts.dashboard-auth')

@section('title', __('auth.singup'))

@section('content')

<style>
    body {
        font-family: 'Zain', sans-serif !important;
        background-color: white;
        margin: 0;
    }

    .signup-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 100px);
        padding: 100px 20px 40px 20px;
        direction: {{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};
        text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
    }

    .signup-box {
        background: #F8F8F8;
        padding: 40px 30px;
        width: 100%;
        max-width: 556px;
        position: relative;
        z-index: 10;
    }

    .signup-title {
        text-align: center;
        margin-bottom: 20px;
        font-size: 24px;
        font-weight: bold;
        font-family: 'Almarai';
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-input {
        width: 100%;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
        font-style: italic;
        transition: border-color 0.3s, box-shadow 0.3s;
        text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
    }

    .submit-wrapper {
        margin-top: 15px;
        display: flex;
    }

    .submit-btn {
        background: #CF9233;
        color: #fff !important;
        font-weight: bold;
        border: none;
        font-family: 'Almarai';
        border-radius: 36px;
        width: 50%;
        margin: auto;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
        padding: 15px 20px;
        font-size: 18px;
    }

    .submit-btn:hover {
        background: #b87f2b;
    }

    .login-link {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }

    .login-link a {
        color: #CF9233;
        font-weight: bold;
        text-decoration: none;
    }

    .login-link a:hover {
        text-decoration: underline;
    }
    .h-10{
        height: 0;
    }
</style>

<div class="signup-wrapper">
    <div class="signup-box">

        <h2 class="signup-title">{{ __('auth.singup') }}</h2>

        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('signup.store') }}">
            @csrf
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">

            <div class="form-group">
                <input type="text" name="username"
                       value="{{ old('username') }}"
                       required autofocus
                       placeholder="{{ __('auth.enter_your_name') }}"
                       class="form-input">
            </div>

            <div class="form-group">
                <input type="text" name="mobile"
                       value="{{ old('mobile') }}"
                       required
                       placeholder="{{ __('auth.mobile') }}"
                       class="form-input">
            </div>

            <div class="submit-wrapper">
                <button type="submit" class="submit-btn">
                    {{ __('auth.singup') }}
                </button>
            </div>
        </form>

        <div class="login-link">
            {{ __('auth.have_account') }}
            <a href="{{ route('login') }}">
                {{ __('auth.login') }}
            </a>
        </div>

    </div>
</div>

@endsection
