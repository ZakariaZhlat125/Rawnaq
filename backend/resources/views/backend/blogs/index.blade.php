@extends('backend.layouts.app')

@section('title', __('blog.title'))

@section('content')
    @include('backend.blogs.partials.alerts')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ __('blog.title') }}</h4>
            @can('add_blog')
                <a href="{{ route('backend.blogs.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> {{ __('blog.add_blog') }}
                </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('blog.blog') }}</th>
                            <th>{{ __('blog.published_at') }}</th>
                            <th>{{ __('blog.status') }}</th>
                            <th class="text-end">{{ __('blog.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($blogs as $blog)
                            @php($frontendBlogUrl = rtrim(config('app.frontend_url'), '/').'/blog/'.$blog->slug)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $blog->image_url }}" alt="{{ $blog->title }}" class="rounded" style="width: 64px; height: 64px; object-fit: cover;">
                                        <div>
                                            <strong>{{ $blog->title }}</strong>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($blog->excerpt ?: strip_tags($blog->content), 90) }}</div>
                                            @if ($blog->status)
                                                <a href="{{ $frontendBlogUrl }}" class="small" target="_blank">{{ __('blog.view_public') }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $blog->published_at ? $blog->published_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>
                                    <span class="badge {{ $blog->status ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $blog->status ? __('blog.active') : __('blog.inactive') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('edit_blog')
                                        <a class="btn btn-sm btn-soft-primary" href="{{ route('backend.blogs.edit', $blog) }}" title="{{ __('blog.edit_blog') }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('delete_blog')
                                        <form class="d-inline" method="POST" action="{{ route('backend.blogs.destroy', $blog) }}" onsubmit="return confirm('{{ __('blog.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-soft-danger" type="submit" title="{{ __('blog.delete') }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('blog.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $blogs->links() }}
        </div>
    </div>
@endsection
