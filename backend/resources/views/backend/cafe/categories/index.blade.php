@extends('backend.layouts.app')

@section('title', __('cafe.categories'))

@section('content')
    @include('backend.cafe.partials.alerts')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ __('cafe.categories') }}</h4>
            <a href="{{ route('backend.cafe.categories.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> {{ __('cafe.add_category') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('cafe.name') }}</th>
                            <th>{{ __('cafe.items_count') }}</th>
                            <th>{{ __('cafe.status') }}</th>
                            <th>{{ __('cafe.sort_order') }}</th>
                            <th class="text-end">{{ __('cafe.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                    @if ($category->description)
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($category->description, 80) }}</div>
                                    @endif
                                </td>
                                <td>{{ $category->items_count }}</td>
                                <td>
                                    <span class="badge {{ $category->status ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $category->status ? __('cafe.active') : __('cafe.inactive') }}
                                    </span>
                                </td>
                                <td>{{ $category->sort_order }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-soft-primary" href="{{ route('backend.cafe.categories.edit', $category) }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form class="d-inline" method="POST" action="{{ route('backend.cafe.categories.destroy', $category) }}" onsubmit="return confirm('{{ __('cafe.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-soft-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ __('cafe.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $categories->links() }}
        </div>
    </div>
@endsection
