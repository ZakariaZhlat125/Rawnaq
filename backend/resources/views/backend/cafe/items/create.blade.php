@extends('backend.layouts.app')

@section('title', __('cafe.add_item'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="card">
        <div class="card-header"><h4 class="mb-0">{{ __('cafe.add_item') }}</h4></div>
        <div class="card-body">
            <form method="POST" action="{{ route('backend.cafe.items.store') }}" enctype="multipart/form-data">
                @include('backend.cafe.items.form')
            </form>
        </div>
    </div>
@endsection
