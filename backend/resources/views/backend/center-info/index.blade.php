@extends('backend.layouts.app')

@section('title', __('center_info.menu_title'))

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.close') }}"></button>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('center_info.page_settings') }}</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('backend.center-info.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">{{ __('center_info.title') }}</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $centerInfo->title) }}" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input type="hidden" name="status" value="0">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" value="1" @checked(old('status', $centerInfo->status))>
                                    <label class="form-check-label" for="status">{{ __('center_info.active_page') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('center_info.about') }}</label>
                                <textarea name="about" class="form-control" rows="4">{{ old('about', $centerInfo->about) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.support_phone') }}</label>
                                <input type="text" name="support_phone" class="form-control" value="{{ old('support_phone', $centerInfo->support_phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.customer_service_phone') }}</label>
                                <input type="text" name="customer_service_phone" class="form-control" value="{{ old('customer_service_phone', $centerInfo->customer_service_phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.whatsapp_url') }}</label>
                                <input type="text" name="whatsapp_url" class="form-control" value="{{ old('whatsapp_url', $centerInfo->whatsapp_url) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.instagram_url') }}</label>
                                <input type="text" name="instagram_url" class="form-control" value="{{ old('instagram_url', $centerInfo->instagram_url) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.facebook_url') }}</label>
                                <input type="text" name="facebook_url" class="form-control" value="{{ old('facebook_url', $centerInfo->facebook_url) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.x_url') }}</label>
                                <input type="text" name="x_url" class="form-control" value="{{ old('x_url', $centerInfo->x_url) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.tiktok_url') }}</label>
                                <input type="text" name="tiktok_url" class="form-control" value="{{ old('tiktok_url', $centerInfo->tiktok_url) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('center_info.map_url') }}</label>
                                <input type="text" name="map_url" class="form-control" value="{{ old('map_url', $centerInfo->map_url) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('center_info.address') }}</label>
                                <textarea name="address" class="form-control" rows="3">{{ old('address', $centerInfo->address) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('center_info.services_info') }}</label>
                                <textarea name="services_info" class="form-control" rows="4">{{ old('services_info', $centerInfo->services_info) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('center_info.extra_info') }}</label>
                                <textarea name="extra_info" class="form-control" rows="4">{{ old('extra_info', $centerInfo->extra_info) }}</textarea>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('center_info.cover_image') }}</label>
                                <input type="file" name="cover_image" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-4">
                                <img src="{{ $centerInfo->cover_image_url }}" alt="{{ $centerInfo->title }}" class="rounded border" style="width: 120px; height: 90px; object-fit: cover;">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">{{ __('center_info.profile_image') }}</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-4">
                                <img src="{{ $centerInfo->profile_image_url }}" alt="{{ $centerInfo->title }}" class="rounded-circle border" style="width: 90px; height: 90px; object-fit: cover;">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">{{ __('center_info.save') }}</button>
                                <a class="btn btn-light" href="{{ $centerInfo->public_url }}" target="_blank">{{ __('center_info.open_public_page') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('center_info.qr_code') }}</h4>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $centerInfo->qr_url }}" alt="{{ __('center_info.qr_code') }}" class="img-fluid border rounded p-2 mb-3" style="max-width: 260px;">
                    <input type="text" class="form-control text-center mb-3" id="center-info-url" value="{{ $centerInfo->public_url }}" readonly>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" onclick="copyCenterInfoUrl()">
                            <i class="fa-regular fa-copy"></i> {{ __('center_info.copy_link') }}
                        </button>
                        <a href="{{ $centerInfo->qr_url }}" target="_blank" class="btn btn-soft-primary">
                            <i class="fa-solid fa-qrcode"></i> {{ __('center_info.open_qr') }}
                        </a>
                    </div>
                    <p class="text-muted small mt-3 mb-0">{{ __('center_info.qr_hint') }}</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyCenterInfoUrl() {
            const input = document.getElementById('center-info-url');
            navigator.clipboard.writeText(input.value);
        }
    </script>
@endsection
