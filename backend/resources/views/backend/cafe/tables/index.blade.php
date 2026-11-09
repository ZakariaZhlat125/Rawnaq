@extends('backend.layouts.app')

@section('title', __('cafe.tables'))

@section('content')
    @include('backend.cafe.partials.alerts')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ __('cafe.tables') }}</h4>
            <a href="{{ route('backend.cafe.tables.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> {{ __('cafe.add_table') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('cafe.table') }}</th>
                            <th>{{ __('cafe.qr_code') }}</th>
                            <th>{{ __('cafe.orders') }}</th>
                            <th>{{ __('cafe.status') }}</th>
                            <th class="text-end">{{ __('cafe.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tables as $table)
                            <tr>
                                <td>
                                    <strong>{{ $table->name }}</strong>
                                    <div class="small text-muted">{{ $table->code }}</div>
                                    <a class="small" href="{{ $table->order_url }}" target="_blank">{{ $table->order_url }}</a>
                                </td>
                                <td><img src="{{ $table->qr_url }}" alt="{{ $table->name }}" style="width: 86px; height: 86px;"></td>
                                <td>{{ $table->orders_count }}</td>
                                <td><span class="badge {{ $table->status ? 'bg-success' : 'bg-secondary' }}">{{ $table->status ? __('cafe.active') : __('cafe.inactive') }}</span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-soft-primary" href="{{ route('backend.cafe.tables.edit', $table) }}"><i class="fa-solid fa-pen"></i></a>
                                    <form class="d-inline" method="POST" action="{{ route('backend.cafe.tables.destroy', $table) }}" onsubmit="return confirm('{{ __('cafe.confirm_delete') }}')">
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
            {{ $tables->links() }}
        </div>
    </div>
@endsection
