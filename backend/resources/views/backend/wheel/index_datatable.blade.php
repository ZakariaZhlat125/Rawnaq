@php
    $lang = app()->getLocale();
@endphp
@extends('backend.layouts.app')

@section('title')
    {{ __('wheel.wheel_of_fortune_management') }}
@endsection

@push('after-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 600;
            color: #444;
        }

        .page-title {
            font-size: 26px;
            font-weight: bold;
            color: white;
            margin: -35px 30px 35px 0;
        }

        .reward-card {
            background: #bf9456;
            color: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s ease;
            position: relative;
        }

        .reward-card:hover {
            transform: translateY(-3px);
        }

        .reward-icon {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .btn-gold {
            background-color: #b89643;
            border: none;
            color: white;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-gold:hover {
            background-color: #a58236;
            color: white;
        }
    </style>
@endpush

@section('content')
    <div class="container py-4">
        <h1 class="page-title"><i class="bi bi-trophy"></i> {{ __('wheel.wheel_of_fortune_management') }}</h1>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-power"></i> {{ __('wheel.wheel_status') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('Wheel.store') }}" method="POST" class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    @csrf
                    <input type="hidden" name="form_type" value="toggle">
                    <input type="hidden" name="wheel_enabled" value="{{ $wheelEnabled ? 0 : 1 }}">

                    <div>
                        <div class="fw-semibold mb-1">
                            {{ $wheelEnabled ? __('wheel.wheel_is_enabled') : __('wheel.wheel_is_disabled') }}
                        </div>
                        <small class="text-muted">{{ __('wheel.wheel_status_hint') }}</small>
                    </div>

                    <button class="btn {{ $wheelEnabled ? 'btn-outline-danger' : 'btn-gold' }}">
                        <i class="bi {{ $wheelEnabled ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                        {{ $wheelEnabled ? __('wheel.disable_wheel') : __('wheel.enable_wheel') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> {{ __('wheel.wheel_display_interval') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('Wheel.store') }}" method="POST" class="row g-3">
                    @csrf
                    <input type="hidden" name="form_type" value="interval">

                    <div class="col-md-6">
                        <label class="form-label">{{ __('wheel.wheel_display_interval') }}</label>
                        <select name="wheel_display_interval_days" class="form-select" required>
                            <option value="1" {{ (int) $wheelDisplayIntervalDays === 1 ? 'selected' : '' }}>
                                {{ __('wheel.daily') }}
                            </option>
                            <option value="10" {{ (int) $wheelDisplayIntervalDays === 10 ? 'selected' : '' }}>
                                {{ __('wheel.every_10_days') }}
                            </option>
                            <option value="30" {{ (int) $wheelDisplayIntervalDays === 30 ? 'selected' : '' }}>
                                {{ __('wheel.every_30_days') }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-12 text-end">
                        <button class="btn btn-gold">
                            <i class="bi bi-save"></i> {{ __('wheel.save_interval') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-gift-fill"></i> {{ __('wheel.add_new_reward') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('Wheel.store') }}" method="POST" class="row g-3">
                    @csrf
                    <input type="hidden" name="form_type" value="reward">

                    <div class="col-md-4">
                        <label class="form-label">{{ __('wheel.gift_type') }}</label>
                        <select name="gift_type" class="form-select" required>
                            <option value="">{{ __('wheel.select_type') }}</option>
                            <option value="points">{{ __('wheel.points') }}</option>
                            <option value="wallet_balance">{{ __('wheel.wallet balance') }}</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('wheel.reward_value') }}</label>
                        <input name="reward_value" type="number" min="1" class="form-control"
                            placeholder="{{ __('wheel.enter_value') }}" required>
                    </div>

                    <div class="col-md-12 text-end">
                        <button class="btn btn-gold">
                            <i class="bi bi-plus-circle"></i> {{ __('wheel.add_reward') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-stars"></i> {{ __('wheel.current_rewards') }}
                </h5>
                <form action="{{ route('Wheel.destroy_all') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i> {{ __('wheel.clear_all') }}
                    </button>
                </form>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($prizes as $prize)
                        <div class="col-md-3">
                            <div class="reward-card text-center p-3">
                                <form action="{{ route('Wheel.destroy', $prize->id) }}" method="POST"
                                    style="position:absolute;top:8px;left:8px;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <div class="reward-icon fs-3">⭐</div>
                                <h6 class="mt-2">{{ str_replace('_', ' ', $prize->type) ?? '-' }}</h6>
                                <p class="mb-1">{{ number_format($prize->reward_value, 0) }}  @if($prize->type=="discount_rate") % @endif </p>
                                <small>
                                    @if ($lang === 'ar')
                                        تهانينا لقد حصلت على

                                        {{ number_format($prize->reward_value, 0) }}
                                        @if($prize->type=="discount_rate") % @endif
                                       @if($prize->type=="points")
                                        نقطة
                                        @elseif($prize->type=="wallet_balance")
                                        رصيد محفظة
                                        @elseif($prize->type=="discount_rate")
                                           نسبة حسم
                                        @endif
                                        هدية تستخدمهم
                                        للخصم على خدمات Sami
                                    @else
                                        Congratulations! You have earned {{ number_format($prize->reward_value, 0) }}
                                        gift
                                        @if($prize->type=="points")
                                            points
                                        @elseif($prize->type=="wallet_balance")
                                            wallet balance
                                            @elseif($prize->type=="discount_rate")
                                            Discount Rate
                                        @endif

                                        to use for discounts on services
                                    @endif
                                </small>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted text-center mb-0">{{ __('wheel.no_rewards_added') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script>
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        @if (session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif

        @if (session('info'))
            toastr.info("{{ session('info') }}");
        @endif
    </script>
@endpush
