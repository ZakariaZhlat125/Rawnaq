@extends('backend.layouts.app')

@section('title', __('cafe.edit_category'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="card">
        <div class="card-header"><h4 class="mb-0">{{ __('cafe.edit_category') }}</h4></div>
        <div class="card-body">
            <form method="POST" action="{{ route('backend.cafe.categories.update', $category) }}">
                @method('PUT')
                @include('backend.cafe.categories.form')
            </form>
        </div>
    </div>
@endsection
