@extends('backend.layouts.app')
@section('title', 'تقرير إحالات التسجيل')
@section('content')
<div dir="rtl">
    <h4 class="mb-3">تقرير إحالات التسجيل</h4>
    <p>التسجيلات المكتملة بكود إحالة بعد تأكيد الجوال. الفترة حسب تاريخ إنشاء الحساب، والنقاط المعروضة هي النقاط الممنوحة وقت التسجيل.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="GET" class="card card-body mb-3"><div class="row g-3">
        <label class="col-md-3">من<input type="date" name="from" value="{{ request('from') }}" class="form-control"></label>
        <label class="col-md-3">إلى<input type="date" name="to" value="{{ request('to') }}" class="form-control"></label>
        <label class="col-md-4">الاسم أو الجوال أو كود الإحالة<input name="search" maxlength="100" value="{{ request('search') }}" class="form-control"></label>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">عرض</button></div>
    </div></form>
    <div class="row mb-3">
        @foreach(['عدد التسجيلات بالإحالة'=>$total, 'النقاط الممنوحة لأصحاب الأكواد'=>$ownerPoints, 'النقاط الممنوحة للأعضاء الجدد'=>$memberPoints] as $label=>$value)
        <div class="col-md-4"><div class="card card-body"><span>{{ $label }}</span><strong class="fs-4">{{ number_format($value ?? 0) }}</strong></div></div>
        @endforeach
    </div>
    <div class="card card-body table-responsive"><table class="table table-striped"><thead><tr>
        <th>المستخدم الجديد</th><th>جواله</th><th>صاحب الكود</th><th>جواله</th><th>الكود المستخدم</th><th>تاريخ التسجيل</th><th>تاريخ منح المكافأة</th><th>نقاط صاحب الكود</th><th>نقاط العضو الجديد</th>
    </tr></thead><tbody>
    @forelse($rows as $row)
        <tr><td>{{ trim($row->first_name.' '.$row->last_name) ?: '#'.$row->id }}</td><td>{{ $row->mobile }}</td>
            <td>{{ trim($row->owner_first_name.' '.$row->owner_last_name) ?: 'مستخدم غير متاح' }}</td><td>{{ $row->owner_mobile ?? '—' }}</td>
            <td>{{ $row->registration_referral_code ?? $row->owner_code ?? '—' }} @if(!$row->registration_referral_code)<small>(سجل قديم)</small>@endif</td>
            <td>{{ $row->created_at }}</td><td>{{ $row->referral_rewarded_at }}</td>
            <td>{{ number_format($row->referral_owner_points ?? $row->legacy_owner_points) }}</td><td>{{ number_format($row->referral_member_points ?? 0) }}</td></tr>
    @empty<tr><td colspan="9">لا توجد تسجيلات بالإحالة مطابقة للبحث.</td></tr>@endforelse
    </tbody></table>{{ $rows->links() }}</div>
    <p class="text-muted">الإحالات القديمة تعرض نقاط صاحب الكود من سجل الولاء والكود الحالي للمحيل إن لم يُحفظ الكود وقت التسجيل. إضافة مكافأة العضو الجديد تسري على التسجيلات الجديدة فقط.</p>
</div>
@endsection
