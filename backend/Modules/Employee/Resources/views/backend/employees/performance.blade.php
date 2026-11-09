@extends('backend.layouts.app')
@section('title', 'تقرير أداء الموظف')
@section('content')
<div dir="rtl">
    <div class="d-flex justify-content-between mb-3"><h4>أداء الموظف: {{ $employee->full_name }}</h4><a href="{{ route('backend.employees.index') }}" class="btn btn-outline-primary">الموظفون</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card card-body mb-3">
        <p>الحالة: <strong>{{ $employee->status ? 'مفعّل' : 'موقوف' }}</strong> | متوسط تقييم العملاء: <strong>{{ $reviewStats['count'] ? number_format($reviewStats['average'], 2).' / 5' : 'لا توجد تقييمات' }}</strong> ({{ $reviewStats['count'] }} تقييم إجمالاً)</p>
        <a href="{{ route('app.booking-reviews') }}">تقييمات الحجوزات والعملاء</a>
        <form method="GET" class="d-flex flex-wrap gap-3 align-items-end mt-3">
            <label>من <input type="date" name="from" value="{{ $from }}" required class="form-control"></label>
            <label>إلى <input type="date" name="to" value="{{ $to }}" required class="form-control"></label>
            <button class="btn btn-primary">عرض التقرير</button><button type="button" class="btn btn-outline-secondary" onclick="window.print()">طباعة</button>
        </form>
    </div>
    <div class="row">
        @foreach(['bookings'=>'الحجوزات', 'completed'=>'المكتملة', 'cancelled'=>'الملغاة', 'services'=>'الخدمات', 'minutes'=>'دقائق العمل المكتمل', 'value'=>'قيمة الخدمات والباقات المدفوعة قبل الضريبة', 'collected'=>'التحصيل الموثق: البوابات والمحفظة والعربون'] as $key=>$label)
        <div class="col-md-4"><div class="card card-body mb-3"><span>{{ $label }}</span><strong class="fs-4">{{ number_format($report['totals'][$key], in_array($key, ['value','collected']) ? 2 : 0) }} {{ in_array($key, ['value','collected']) ? 'ر.س' : '' }}</strong></div></div>
        @endforeach
    </div>
    <p class="text-muted">الفترة حسب تاريخ موعد الحجز. التحصيل هو حصة الموظف من الدفعات المسجلة، موزعة بنسبة قيمة خدماته وباقاته إلى إجمالي السلة قبل الضريبة والخصومات. المدفوعات الجديدة تحفظ توزيع البوابة والمحفظة والعربون؛ السجلات القديمة تعتمد على دفعات البوابة والعربون المتاحة وقد لا تتضمن المحفظة. لا تُحتسب نقاط الولاء أو النقد غير المسجل، ولا يُعد السعر الكامل للدفع عند الوصول مبلغاً محصلاً. التقييمات تشمل آراء العملاء قبل اعتماد نشرها، ويحتسب رأي واحد لكل عميل وحجز وموظف.</p>
    <div class="card table-responsive"><table class="table"><thead><tr><th>الحجز</th><th>الموعد</th><th>العميل</th><th>الحالة</th><th>الخدمات</th><th>القيمة</th><th>السداد</th><th>التحصيل الموثق</th></tr></thead><tbody>
    @forelse($report['rows'] as $row)
    <tr><td>#{{ $row['booking']->id }}</td><td>{{ $row['booking']->start_date_time }}</td><td>{{ $row['booking']->user?->full_name ?? '—' }}</td><td>{{ $row['booking']->status }}</td><td>{{ $row['services'] }}</td><td>{{ number_format($row['value'],2) }}</td><td>{{ $row['paid'] ? 'مسجل كمدفوع' : 'غير مدفوع' }}</td><td>{{ $row['has_collection_record'] ? number_format($row['collected'],2) : 'لا يوجد سجل تحصيل مفصل' }}</td></tr>
    @empty<tr><td colspan="8">لا توجد حجوزات خلال الفترة.</td></tr>@endforelse
    </tbody></table></div>
    <div class="card card-body mt-3"><h5>تقييمات الموظف خلال الفترة</h5>
        @forelse($votes as $vote)<div class="border-bottom py-2">الحجز #{{ $vote['booking_id'] ?? '—' }} | {{ number_format($vote['rating'],2) }} / 5 | {{ $vote['date'] }}<p class="mb-0">{{ $vote['comment'] }}</p></div>@empty<p>لا توجد تقييمات خلال الفترة.</p>@endforelse
    </div>
    @hasPermission('edit_staff')
    <form method="POST" action="{{ route('backend.employees.schedule', $employee->id) }}" class="card card-body mt-3">
        @csrf @method('PUT')
        <h5>تغيير وردية الموظف</h5>
        <p>اختر الوردية الأساسية. عند التناوب الأسبوعي تكون الوردية المختارة هي وردية أسبوع تاريخ البداية (الأسبوع يبدأ الأحد). يعمل التناوب عند وجود ورديتين نشطتين فقط؛ وإلا تستخدم الوردية الأساسية.</p>
        <label>الوردية<select class="form-select mb-3" name="shift_id" required>@foreach($shifts as $shift)<option value="{{ $shift->id }}" @selected(old('shift_id', $assignment?->shift_id) == $shift->id)>{{ $shift->name }}</option>@endforeach</select></label>
        <label>النظام<select name="shift_rotation_enabled" class="form-select mb-3"><option value="0" @selected(!old('shift_rotation_enabled', $assignment?->shift_rotation_enabled ?? true))>وردية ثابتة</option><option value="1" @selected(old('shift_rotation_enabled', $assignment?->shift_rotation_enabled ?? true))>تناوب أسبوعي</option></select></label>
        <label>تاريخ بداية الوردية الأساسية<input type="date" name="shift_rotation_anchor" required value="{{ old('shift_rotation_anchor', $assignment?->shift_rotation_anchor ?? '2026-02-15') }}" class="form-control mb-3"></label>
        <button class="btn btn-primary" @disabled(!$assignment)>حفظ الوردية</button>
    </form>
    @endhasPermission
</div>
@endsection
