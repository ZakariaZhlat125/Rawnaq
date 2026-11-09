@extends('backend.layouts.app')

@section('title', __('cafe.add_table'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="card">
        <div class="card-header"><h4 class="mb-0">{{ __('cafe.add_table') }}</h4></div>
        <div class="card-body">
            <form method="POST" action="{{ route('backend.cafe.tables.store') }}">
                @include('backend.cafe.tables.form')
            </form>
        </div>
    </div>
@endsection
