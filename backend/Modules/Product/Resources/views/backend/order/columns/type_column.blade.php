@if ($data->orderGroup->type == 'booking')
    <span class="badge bg-soft-danger rounded-pill text-capitalize">
         {{ __('Booking') }} 
    </span>
@else
    <span class="badge bg-soft-primary rounded-pill text-capitalize">
       {{ __('Online') }}
    </span>
@endif