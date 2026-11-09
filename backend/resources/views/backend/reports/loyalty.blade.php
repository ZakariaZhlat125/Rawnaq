@extends('backend.layouts.app')
@section('title','تقرير نقاط الولاء')
@section('content')
<div dir="rtl"><h4>تقرير نقاط الولاء</h4>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" class="card card-body"><div class="row g-3">
<label class="col-md-2">من<input type="date" name="from" value="{{ request('from') }}" class="form-control"></label>
<label class="col-md-2">إلى<input type="date" name="to" value="{{ request('to') }}" class="form-control"></label>
<label class="col-md-3">المستخدم: الاسم أو الجوال أو البريد<input name="search" maxlength="100" value="{{ request('search') }}" class="form-control"></label>
<label class="col-md-3">الحركة<select name="action" class="form-select"><option value="">الكل</option><option value="add" @selected(request('action')==='add')>إضافة نقاط</option><option value="deduct" @selected(request('action')==='deduct')>استخدام / خصم نقاط</option></select></label>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">عرض</button></div>
</div></form>
<div class="row mt-3">@foreach(['النقاط المضافة'=>$added,'النقاط المستخدمة / المخصومة'=>$used,'عدد المستخدمين'=>$users] as $label=>$value)<div class="col-md-4"><div class="card card-body"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></div></div>@endforeach</div>
<p>الإحصاءات حسب الفلاتر المختارة. الرصيد المعروض هو الرصيد بعد الحركة وقت تسجيلها، وليس الرصيد الحالي. التقرير يعرض الحركات المسجلة فقط.</p>
<div class="card card-body table-responsive"><table class="table table-striped"><thead><tr><th>التاريخ</th><th>المستخدم</th><th>الجوال</th><th>الحركة</th><th>النقاط</th><th>الرصيد بعد الحركة</th><th>السبب / المصدر</th><th>مرجع المصدر</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->created_at }}</td><td>{{ $row->user?->full_name ?? '#'.$row->user_id }}</td><td>{{ $row->user?->mobile ?? '—' }}</td><td>{{ $row->action==='add' ? 'إضافة' : ($row->action==='deduct' ? 'استخدام / خصم' : $row->action) }}</td><td>{{ number_format($row->points) }}</td><td>{{ number_format($row->balance_after) }}</td><td>{{ ['quiet_hours'=>__('quiet_hours.reward_label'),'referral'=>'مكافأة صاحب كود الإحالة','referral_registration'=>'مكافأة التسجيل بكود إحالة','Loyalty points payment deduction'=>'استخدام النقاط للدفع'][$row->source] ?? $row->source }}</td><td>{{ $row->source_id ?? '—' }}</td></tr>
@empty<tr><td colspan="8">لا توجد حركات نقاط مطابقة.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div>
@hasPermission('view_financial_report')<a class="btn btn-outline-primary" href="{{ route('backend.reports.financial-report') }}">تقرير الإيرادات</a>@endhasPermission
</div>
@endsection
