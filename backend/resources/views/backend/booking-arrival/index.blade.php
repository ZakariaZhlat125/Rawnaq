@extends('backend.layouts.app')
@section('title', 'تأكيد وصول العملاء')
@section('content')
<div dir="rtl">
<h4>تأكيد وصول العملاء</h4>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p>امسح رمز الحجز بكاميرا الهاتف لفتح بياناته وتأكيد وصول العميل، أو استخدم مفتاح التأكيد اليدوي.</p>
<details class="card card-body mb-3"><summary>مسح QR بالكاميرا</summary>
<div class="my-2"><button id="start-arrival-scan" type="button" class="btn btn-primary">فتح الكاميرا</button> <button id="stop-arrival-scan" type="button" class="btn btn-secondary" disabled>إيقاف الكاميرا</button></div>
<video id="arrival-camera" playsinline muted style="width:100%;max-width:400px" hidden></video>
<p id="arrival-scan-message" role="status"></p>
<form id="arrival-scan-link-form"><label for="arrival-scan-link">أو الصق رابط رمز الحجز</label><input id="arrival-scan-link" class="form-control" type="url" required><button class="btn btn-primary mt-2">فتح الحجز</button></form>
</details>
<form class="d-flex gap-2 mb-3" method="GET">
    <input class="form-control" type="number" name="booking_id" min="1" placeholder="رقم الحجز" value="{{ request('booking_id') }}">
    <button class="btn btn-primary">بحث</button>
</form>
<div class="table-responsive"><table class="table">
<thead><tr><th>الحجز</th><th>العميل</th><th>الفرع والموعد</th><th>تأكيد الوصول</th><th>بيانات الحجز وQR</th></tr></thead>
<tbody>@forelse($bookings as $booking)
<tr>
<td>#{{ $booking->id }}<br>{{ $booking->status }}</td>
<td>{{ $booking->user?->full_name }}</td>
<td>{{ $booking->branch?->name }}<br>{{ $booking->start_date_time }}</td>
<td>
@if(in_array($booking->status, ['pending','confirmed','check_in']))
<form method="POST" action="{{ route('backend.booking-arrival.update', $booking) }}">
@csrf <input type="hidden" name="source" value="manual"><input type="hidden" name="arrived" value="0">
<div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="arrived" value="1" id="arrived-{{ $booking->id }}" @checked($booking->arrived_at) onchange="this.form.requestSubmit()"><label class="form-check-label" for="arrived-{{ $booking->id }}">{{ $booking->arrived_at ? 'تم الوصول' : 'لم يصل بعد' }}</label></div>
</form>
@else {{ $booking->arrived_at ? 'تم الوصول' : 'لم يؤكد الوصول' }} @endif
<small>{{ $booking->arrived_at }}</small>
</td>
<td><a class="btn btn-outline-primary" href="{{ route('backend.booking-arrival.show', $booking) }}">عرض البيانات وQR</a></td>
</tr>
@empty <tr><td colspan="5">لا توجد حجوزات.</td></tr>@endforelse</tbody>
</table></div>{{ $bookings->links() }}
</div>
@endsection
@push('after-scripts')
<script src="{{ asset('js/booking-arrival-scanner.js') }}"></script>
@endpush
