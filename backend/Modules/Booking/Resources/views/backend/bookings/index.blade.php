@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@push('after-styles')
    {{-- <link rel="stylesheet" href='{{ mix("modules/booking/style.css") }}'> --}}

@endpush
@section('banner-button')
@hasPermission('edit_booking')
<a class="btn btn-success" href="{{ route('backend.booking-arrival.index') }}"><i class="fas fa-qrcode"></i> تأكيد الوصول وQR</a>
@endhasPermission
@hasPermission('add_booking')
<button type="button" class="btn btn-primary" data-booking-create><i class="fa-solid fa-plus"></i> {{ __('booking.lbl_new_appointment') }}</button>
<button type="button" class="btn btn-soft-primary" data-home-booking-create data-bs-toggle="offcanvas" data-bs-target="#home-booking-form" aria-controls="home-booking-form">
    <i class="fa-solid fa-house me-2"></i>{{ __('messagess.home_services') }}
</button>
@endhasPermission
@hasPermission('booking_booking_tableview')
<a href="{{route("backend.$module_name.datatable_view")}}" class="btn btn-dark"><i class="fa-solid fa-table"></i> {{ __('messages.datatable_view') }}</a>
@endhasPermission
@endsection
@section('content')

<div class="card">
    <div class="card-body">
      <div data-render="app">
        <calendar-view
          slot-duration="{{ setting('slot_duration') }}"
          status="{{ json_encode($statusList) }}"
          :branch-id="{{ $selected_branch->id ?? 0 }}"
          :can-reorder="{{ auth()->user()->can('view_booking') ? 'true' : 'false' }}"
          date="{{ \Carbon\Carbon::parse($date)->toDateString() }}"
          ></calendar-view>
        <home-booking-form></home-booking-form>
      </div>
    </div>
</div>

@endsection

@push ('after-scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ mix("modules/booking/script.js") }}"></script>
<script>
document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-booking-create]')) {
        if (!event.target.closest('[data-home-booking-create]')) {
            return;
        }

        if (typeof window.openHomeBookingForm === 'function') {
            window.openHomeBookingForm();
            return;
        }

        const homeBookingElement = document.getElementById('home-booking-form');
        if (homeBookingElement && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            bootstrap.Offcanvas.getOrCreateInstance(homeBookingElement).show();
        }

        window.dispatchEvent(new CustomEvent('home-booking:create'));
        return;
    }

    window.dispatchEvent(new CustomEvent('booking:create'));
});
</script>

@endpush
