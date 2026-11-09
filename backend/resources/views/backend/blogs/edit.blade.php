@extends('backend.layouts.app')

@section('title', __('blog.edit_blog'))

@section('content')
    @include('backend.blogs.partials.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">{{ __('blog.edit_blog') }}</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('backend.blogs.update', $blog) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('backend.blogs.form')
            </form>
        </div>
    </div>
@endsection
