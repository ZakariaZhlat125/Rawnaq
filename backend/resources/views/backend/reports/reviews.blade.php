@extends('backend.layouts.app')
@section('title', 'تقرير التقييمات')
@section('content')
@php($types=['branch'=>'الفروع','service'=>'الخدمات','employee'=>'الموظفين','booking'=>'الحجوزات العامة القديمة'])
<div dir="rtl">
    <h4>تقرير التقييمات</h4>
    <p>تقييمات الفروع والخدمات والموظفين، بما فيها التقييمات غير المعتمدة والمخفية وفق الفلاتر. الفترة حسب تاريخ إرسال التقييم.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="GET" class="card card-body"><div class="row g-3">
        <label class="col-md-3">من<input type="date" name="from" value="{{ request('from') }}" class="form-control"></label>
        <label class="col-md-3">إلى<input type="date" name="to" value="{{ request('to') }}" class="form-control"></label>
        <label class="col-md-3">النوع<select name="type" class="form-select"><option value="">الكل</option>@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></label>
        <label class="col-md-3">الفرع<select name="branch_id" class="form-select"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id')==$branch->id)>{{ \App\Services\ReviewReport::name($branch->name) }}</option>@endforeach</select></label>
        <label class="col-md-3">النجوم<select name="rating" class="form-select"><option value="">الكل</option>@for($n=1;$n<=5;$n++)<option value="{{ $n }}" @selected(request('rating')==$n)>{{ $n }} / 5</option>@endfor</select></label>
        <label class="col-md-3">الاعتماد<select name="approval" class="form-select"><option value="">الكل</option><option value="approved" @selected(request('approval')==='approved')>معتمد</option><option value="pending" @selected(request('approval')==='pending')>بانتظار الاعتماد</option></select></label>
        <label class="col-md-3">الظهور<select name="visibility" class="form-select"><option value="">الكل</option><option value="visible" @selected(request('visibility')==='visible')>ظاهر</option><option value="hidden" @selected(request('visibility')==='hidden')>مخفي</option></select></label>
        <div class="col-md-3 d-flex gap-2 align-items-end"><button class="btn btn-primary">عرض</button><a class="btn btn-outline-primary" href="{{ route('app.booking-reviews') }}">إدارة التقييمات</a></div>
    </div></form>
    <div class="row mt-3">@foreach($stats as $key=>$stat)<div class="col-md-3"><div class="card card-body"><strong>{{ $types[$key] }}</strong><span>{{ $stat['count'] }} تقييم | المتوسط {{ $stat['count'] ? number_format($stat['average'],2).' / 5' : '—' }}</span></div></div>@endforeach</div>
    <div class="card card-body table-responsive"><table class="table table-striped"><thead><tr><th>النوع</th><th>الفرع / الخدمة / الموظف</th><th>الفرع</th><th>الحجز</th><th>العميل</th><th>التقييم</th><th>التعليق</th><th>التاريخ</th><th>الاعتماد</th><th>الظهور</th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td>{{ $types[$row['type']] }}</td><td>{{ \App\Services\ReviewReport::name($row['target']) }}</td><td>{{ \App\Services\ReviewReport::name($row['branch']) }}</td><td>#{{ $row['booking_id'] ?? '—' }}</td><td>{{ $row['customer'] ?: '—' }}</td><td>{{ number_format($row['rating'],1) }} / 5</td><td>{{ $row['comment'] }}</td><td>{{ $row['date'] }}</td><td>{{ $row['approved'] === null ? 'سجل قديم دون اعتماد' : ($row['approved'] ? 'معتمد' : 'بانتظار الاعتماد') }}</td><td>{{ $row['visible'] ? 'ظاهر' : 'مخفي' }}</td></tr>
    @empty<tr><td colspan="10">لا توجد تقييمات مطابقة.</td></tr>@endforelse
    </tbody></table>{{ $rows->links() }}</div>
    <p class="text-muted">كل تقييم خدمة أو موظف داخل الحجز يمثل صفاً مستقلاً. لا يضاف التقييم العام للحجز كتصويت إضافي عند وجود تقييم صريح للفرع، ولا يكرر تقييم الموظف القديم إذا توفر تقييم حديث لنفس العميل والحجز والموظف.</p>
</div>
@endsection
