@extends('backend.layouts.app')
@section('title', __('service_repeat.title'))
@section('content')
<div class="card"><div class="card-body">
<h3>{{ __('service_repeat.title') }}</h3>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p>{{ __('service_repeat.policy') }}</p><p>{{ __('service_repeat.changes') }}</p>
@can('store_loyalty')
<form method="post" action="{{ route('backend.service-repeat.toggle') }}" class="border rounded p-3 mb-4">
@csrf<input type="hidden" name="enabled" value="0">
<label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="enabled" value="1" @checked($enabled)> {{ __('service_repeat.global') }}</label>
<button class="btn btn-primary">{{ __('service_repeat.save') }}</button>
</form>
@endcan
@foreach($services as $service)
@php($rule = $rules->get($service->id))
<form method="post" action="{{ route('backend.service-repeat.store') }}" class="border rounded p-3 mb-3">
@csrf
<input type="hidden" name="service_id" value="{{ $service->id }}">
<h5>{{ $service->name }}</h5>
<input type="hidden" name="enabled" value="0">
<label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="enabled" value="1" @checked($rule?->enabled)> {{ __('service_repeat.enabled') }}</label>
<label for="visits-{{ $service->id }}">{{ __('service_repeat.visits') }}</label>
<input class="form-control mb-2" id="visits-{{ $service->id }}" name="required_visits" type="number" min="1" max="1000" value="{{ $rule?->required_visits ?? 5 }}" required>
@can('store_loyalty')<button class="btn btn-primary">{{ __('service_repeat.save') }}</button>@endcan
</form>
@endforeach
<h4>{{ __('service_repeat.history') }}</h4>
<div class="table-responsive"><table class="table"><thead><tr><th>#</th><th>{{ __('service_repeat.customer') }}</th><th>{{ __('service_repeat.service') }}</th><th>{{ __('service_repeat.status') }}</th></tr></thead><tbody>
@forelse($rewards as $reward)<tr><td>{{ $reward->id }}</td><td>{{ $reward->first_name }} {{ $reward->last_name }} (#{{ $reward->user_id }})</td><td>{{ $services->firstWhere('id',$reward->service_id)?->name }}</td><td>{{ __('service_repeat.'.($reward->used_at ? 'used' : ($reward->booking_id ? 'reserved' : 'available'))) }}</td></tr>
@empty<tr><td colspan="4">{{ __('service_repeat.none') }}</td></tr>@endforelse
</tbody></table></div>{{ $rewards->links() }}
</div></div>
@endsection
