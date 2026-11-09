@extends('backend.layouts.app')

@php
    $t = fn ($k) => __('gift_system.admin.'.$k);
    $occasions = ['general', 'birthday', 'eid', 'ramadan', 'national_day', 'wedding', 'christmas', 'thanks'];
@endphp

@section('title') {{ $t('designs') }} @endsection

@section('content')
@include('backend.gift._nav')



<div class="card mb-3">
    <div class="card-body">
        <h5>{{ $t('add_design') }}</h5>
        <form method="post" action="{{ route('gift-designs.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            @include('backend.gift._design_fields')
            <div class="col-12"><button class="btn btn-primary btn-sm">{{ $t('save') }}</button></div>
        </form>
    </div>
</div>

@foreach ($designs as $d)
    <div class="card mb-2">
        <div class="card-body">
            <div class="d-flex gap-3 align-items-start">
                <div style="width:120px;aspect-ratio:16/10;border-radius:10px;flex:none;display:grid;place-items:center;font-weight:700;background:{{ $d->background }};color:{{ $d->foreground }};@if($d->image_url)background-image:url('{{ $d->image_url }}');background-size:cover;@endif">
                    {{ $d->icon ?: '✦' }} SAMI
                </div>
                <div class="flex-grow-1">
                    <div class="mb-2"><code>{{ $d->slug }}</code> · <small class="text-muted">{{ __('gift_system.admin.in_use', ['count' => $usage[$d->slug] ?? 0]) }}</small>
                        @unless ($d->is_active)<span class="badge bg-secondary ms-1">{{ $t('no') }} {{ $t('active') }}</span>@endunless
                    </div>
                    <form method="post" action="{{ route('gift-designs.update', $d) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf @method('PUT')
                        @include('backend.gift._design_fields', ['d' => $d])
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary btn-sm">{{ $t('save') }}</button>
                        </div>
                    </form>
                    <form method="post" action="{{ route('gift-designs.destroy', $d) }}" class="mt-1" onsubmit="return confirm('{{ $t('delete') }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-link btn-sm text-danger p-0">{{ $t('delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
