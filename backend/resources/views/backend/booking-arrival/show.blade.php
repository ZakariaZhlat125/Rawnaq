@extends('backend.layouts.app')
@section('title', 'بيانات الحجز وتأكيد الوصول')
@section('content')
<div class="card card-body" dir="rtl">
    <h4>الحجز #{{ $booking->id }}</h4>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    {{-- تحذير إذا كان الموظف من فرع غير فرع الحجز --}}
    @if(!empty($branchMismatch))
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
            <div>
                <strong>تحذير:</strong> أنت مسجل في فرع مختلف عن فرع هذا الحجز ({{ $booking->branch?->name }}). يرجى التأكد قبل تنفيذ الإجراءات.
            </div>
        </div>
    @endif

    {{-- تنبيه إذا كان الحجز غير مدفوع أو عليه مبلغ متبقٍ --}}
    @if(empty($isPaid))
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-money-bill-wave me-2 fs-5"></i>
            <div>
                <strong>تنبيه مالي:</strong> هذا الحجز غير مدفوع أو عليه مبالغ متبقية. لا يظهر رمز الدخول للعميل إلا بعد إتمام الدفع.
            </div>
        </div>
    @endif

    <div class="row mt-3">
        <div class="col-md-6">
            <p><strong>العميل:</strong> {{ $booking->user?->full_name }} — <span dir="ltr">{{ $booking->user?->mobile }}</span></p>
            <p><strong>الفرع:</strong> {{ $booking->branch?->name }}</p>
            <p><strong>الموعد:</strong> {{ $booking->start_date_time }}</p>
            <p><strong>حالة الحجز:</strong> <span class="badge bg-primary">{{ $booking->status }}</span></p>
            <p><strong>حالة الدفع:</strong> 
                @if(!empty($isPaid))
                    <span class="badge bg-success">مدفوع</span>
                @else
                    <span class="badge bg-danger">غير مدفوع / متبقي</span>
                @endif
            </p>
        </div>
        <div class="col-md-6">
            <p><strong>الخدمات:</strong></p>
            <ul class="list-unstyled">
                @foreach($booking->booking_service ?? [] as $service)
                    <li><i class="fas fa-check text-success me-1"></i> {{ $service->service?->name }} — {{ $service->service_price }} ريال</li>
                @endforeach
            </ul>
            <p><strong>حالة الوصول:</strong> {{ $booking->arrived_at ? 'تم تأكيد وصول العميل في '.$booking->arrived_at : 'لم يتم تأكيد الوصول بعد' }}</p>
            @if($booking->arrived_at)
                <p><strong>مسجل الوصول:</strong> {{ $booking->arrivalConfirmedBy?->full_name }} — <strong>الطريقة:</strong> {{ $booking->arrival_source === 'qr' ? 'مسح QR' : 'تأكيد يدوي' }}</p>
            @endif
        </div>
    </div>

    @if(in_array($booking->status, ['pending','confirmed','check_in']))
        <form method="POST" action="{{ $fromScan ? $qrUrl : route('backend.booking-arrival.update', $booking) }}" class="my-3">
            @csrf
            <input type="hidden" name="source" value="{{ $fromScan ? 'qr' : 'manual' }}">
            <input type="hidden" name="arrived" value="{{ $booking->arrived_at ? '0' : '1' }}">
            <button class="btn {{ $booking->arrived_at ? 'btn-outline-secondary' : 'btn-success' }} px-4 py-2">
                <i class="fas {{ $booking->arrived_at ? 'fa-undo' : 'fa-check' }}"></i>
                {{ $booking->arrived_at ? 'إلغاء تأكيد الوصول' : 'تأكيد وصول العميل' }}
            </button>
        </form>
    @endif

    {{-- عرض الـ QR في حال كان الحجز مدفوعاً وصالحاً --}}
    @if(!empty($isPaid) && !empty($qrUrl))
        <div class="my-3 text-center d-inline-block" style="background:#f9f9f9;padding:20px;border-radius:12px;border:1px solid #ddd;">
            <div id="arrival-qr" data-url="{{ $qrUrl }}" style="width:240px;height:240px;margin:auto;"></div>
            <div class="mt-3">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="saveQrAsImage()">
                    <i class="fas fa-download me-1"></i> حفظ كصورة
                </button>
            </div>
            <small class="d-block text-muted mt-2">الرمز صالح حتى نهاية يوم الحجز فقط، ويتطلب تسجيل الدخول بصفة موظف.</small>
        </div>
    @else
        <div class="alert alert-secondary my-3">
            <i class="fas fa-lock me-1"></i> رمز QR غير ظاهر لأن الحجز غير مدفوع أو انتهى تاريخه.
        </div>
    @endif

    <div class="mt-4">
        <a class="btn btn-secondary" href="{{ route('backend.booking-arrival.index') }}">العودة إلى قائمة الوصول</a>
    </div>
</div>
@endsection

@push('after-scripts')
@if(!empty($isPaid) && !empty($qrUrl))
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const arrivalQr = document.getElementById('arrival-qr');
    if (arrivalQr) {
        new QRCode(arrivalQr, {
            text: arrivalQr.dataset.url,
            width: 240,
            height: 240,
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});

function saveQrAsImage() {
    const img = document.querySelector('#arrival-qr img');
    if (img && img.src) {
        const a = document.createElement('a');
        a.href = img.src;
        a.download = 'booking-{{ $booking->id }}-qr.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    } else {
        const canvas = document.querySelector('#arrival-qr canvas');
        if (canvas) {
            const a = document.createElement('a');
            a.href = canvas.toDataURL('image/png');
            a.download = 'booking-{{ $booking->id }}-qr.png';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    }
}
</script>
@endif
@endpush
