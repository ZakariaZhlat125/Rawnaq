@extends('backend.layouts.app')

@section('title', __('cafe.items'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ __('cafe.items') }}</h4>
            <a href="{{ route('backend.cafe.items.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> {{ __('cafe.add_item') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('cafe.item') }}</th>
                            <th>{{ __('cafe.category') }}</th>
                            <th>{{ __('cafe.price') }}</th>
                            <th>{{ __('cafe.status') }}</th>
                            <th class="text-end">{{ __('cafe.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="rounded" style="width: 52px; height: 52px; object-fit: cover;">
                                        <div>
                                            <strong>{{ $item->name }}</strong>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($item->description, 70) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item->category?->name }}</td>
                                <td>{{ number_format($item->price, 2) }}</td>
                                <td><span class="badge {{ $item->status === 'available' ? 'bg-success' : 'bg-secondary' }}">{{ $item->status === 'available' ? __('cafe.available') : __('cafe.unavailable') }}</span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-soft-primary" href="{{ route('backend.cafe.items.edit', $item) }}"><i class="fa-solid fa-pen"></i></a>
                                    <form class="d-inline" method="POST" action="{{ route('backend.cafe.items.destroy', $item) }}" onsubmit="return confirm('{{ __('cafe.confirm_delete') }}')">
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
            {{ $items->links() }}
        </div>
    </div>
@endsection
