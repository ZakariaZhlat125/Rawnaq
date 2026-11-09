@extends('backend.layouts.app')
@section('title') {{ __('faq.title') }} @endsection
@section('content')
<p>{{ __('faq.note') }}</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('backend.faq.update') }}" id="faq-form">
    @csrf @method('PUT')
    <div id="faq-rows">
    @foreach(old('items', $rows) as $index => $row)
        @include('backend.faq.row', compact('row', 'index'))
    @endforeach
    </div>
    <button type="button" class="btn btn-secondary" id="faq-add">{{ __('faq.add') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('faq.save') }}</button>
</form>
<template id="faq-template">@include('backend.faq.row', ['row' => [], 'index' => '__INDEX__'])</template>
@endsection
@push('after-scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.getElementById('faq-rows');
    let next = Math.max(0, ...Array.from(rows.querySelectorAll('[name]'), input => Number(input.name.match(/items\[(\d+)\]/)?.[1] || 0))) + 1;
    document.getElementById('faq-add').addEventListener('click', () => {
        rows.insertAdjacentHTML('beforeend', document.getElementById('faq-template').innerHTML.replaceAll('__INDEX__', next++));
    });
    rows.addEventListener('click', event => {
        const row = event.target.closest('.faq-editor-row');
        if (!row) return;
        if (event.target.closest('[data-faq-remove]')) row.remove();
        if (event.target.closest('[data-faq-up]') && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
        if (event.target.closest('[data-faq-down]') && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
    });
    document.getElementById('faq-form').addEventListener('submit', () => {
        Array.from(rows.children).forEach((row, index) => row.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/items\[[^\]]+\]/, `items[${index}]`);
        }));
    });
});
</script>
@endpush
