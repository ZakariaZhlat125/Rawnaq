@extends('backend.layouts.app')

@section('title')
{{ __($module_action) }} - {{ $customer->full_name }}
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <x-backend.section-header>
            <div>
                <h5 class="mb-0">{{ __('messages.loyalty_points') }} - {{ $customer->full_name }}</h5>
                <small class="text-muted">{{ $customer->email ?? $customer->mobile }}</small>
            </div>
            <x-slot name="toolbar">
                <a href="{{ route('backend.customers.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> {{ __('messages.back') }}
                </a>
            </x-slot>
        </x-backend.section-header>

        <div class="table-responsive">
            <table class="table table-striped border">
                <thead>
                    <tr>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.type') }}</th>
                        <th>{{ __('messages.points') }}</th>
                        <th>{{ __('messages.balance') }}</th>
                        <th>{{ __('messages.source') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ optional($tx->created_at)->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($tx->action === 'add')
                                    <span class="badge bg-soft-success">{{ __('messages.add') }}</span>
                                @else
                                    <span class="badge bg-soft-danger">{{ __('messages.deduct') }}</span>
                                @endif
                            </td>
                            <td>{{ $tx->points }}</td>
                            <td>{{ $tx->balance_after ?? '-' }}</td>
                            <td>{{ $tx->source ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">{{ __('messages.no_data_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
