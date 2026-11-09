@extends('backend.layouts.app')
@section('title', 'العضويات والصلاحيات')
@section('content')
<style>
.access-panel{direction:rtl;max-width:1440px;margin:auto}.access-hero{background:linear-gradient(120deg,#152e36,#31665e);color:white;border-radius:20px;padding:32px;margin-bottom:24px}.access-hero h2{color:white}.access-panel .card{border:1px solid #e4e9e8;border-radius:16px;box-shadow:0 6px 24px #16352d08}.access-panel label{display:block;margin-bottom:8px}.access-panel .permission-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px;max-height:360px;overflow:auto;padding:12px;background:#f6f8f7;border-radius:12px}.access-panel .permission-grid label{color:#263d38;background:white;padding:10px;border-radius:8px;overflow-wrap:anywhere;margin:0}.access-panel input[type=checkbox]{margin-inline-end:8px}.access-panel .table td{vertical-align:middle}.access-panel .badge{margin:3px}.access-panel .form-control,.access-panel .form-select{margin-bottom:14px}.access-panel summary{cursor:pointer;font-weight:600;padding:12px 0}
</style>
<div class="access-panel">
    <div class="access-hero"><span>إدارة الفريق والوصول</span><h2 class="mt-2">العضويات والصلاحيات</h2><p class="mb-0">نظّم حسابات فريقك وحدد صلاحيات كل دور من مكان واحد.</p></div>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-3 mb-4">
        @foreach(['العضويات' => $users->total(), 'الأدوار' => $roles->count(), 'الصلاحيات المتاحة' => $permissions->count()] as $label => $count)
        <div class="col-md-4"><div class="card p-4 mb-0"><span class="text-muted">{{ $label }}</span><strong class="fs-2">{{ $count }}</strong></div></div>
        @endforeach
    </div>
    <div class="card p-4 mb-4"><div class="d-flex flex-wrap justify-content-between gap-3"><h4>دليل العضويات</h4><form method="GET" class="d-flex gap-2"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو البريد أو الجوال" aria-label="بحث العضويات"><button class="btn btn-primary mb-3">بحث</button></form></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>العضو</th><th>الأدوار</th><th>الحالة</th><th>الإجراء</th></tr></thead><tbody>
        @forelse($users as $member)
        <tr><td><strong>{{ $member->first_name }} {{ $member->last_name }}</strong><div class="text-muted" dir="ltr">{{ $member->email }}</div></td><td>@foreach($member->roles as $assigned)<span class="badge bg-primary">{{ $assigned->title ?: $assigned->name }}</span>@endforeach</td><td><span class="badge {{ $member->status ? 'bg-success' : 'bg-secondary' }}">{{ $member->status ? 'نشط' : 'موقوف' }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('backend.access.index', ['member' => $member->id]) }}#member-form">تعديل العضوية</a></td></tr>
        @empty<tr><td colspan="4" class="text-center py-4">لا توجد عضويات مطابقة للبحث.</td></tr>@endforelse
        </tbody></table></div>{{ $users->links() }}
    </div>
    <div class="card p-4 mb-4" id="member-form"><h4>{{ $editingUser ? 'تعديل العضوية' : 'إضافة عضوية' }}</h4><p class="text-muted">تُجمع صلاحيات الأدوار المختارة. إدارة العضويات متاحة للمدير فقط. تظل أي صلاحيات مباشرة ممنوحة سابقًا للحساب سارية.</p>
        <form method="POST" action="{{ $editingUser ? route('backend.access.members.update', $editingUser) : route('backend.access.members.store') }}">@csrf @if($editingUser)@method('PUT')@endif
            <div class="row">@foreach(['first_name' => 'الاسم الأول', 'last_name' => 'اسم العائلة', 'email' => 'البريد الإلكتروني', 'mobile' => 'الجوال'] as $field => $label)
                <div class="col-md-6"><label for="member-{{ $field }}">{{ $label }}</label><input id="member-{{ $field }}" class="form-control" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'mobile' ? 'tel' : 'text') }}" value="{{ old($field, $editingUser?->$field) }}" required maxlength="{{ $field === 'mobile' ? 20 : 191 }}"></div>
            @endforeach
            </div>
            <p class="text-muted">تسجيل الدخول برمز التحقق (OTP) عبر رقم الجوال. أدخل رقمًا سعوديًا مثل 05xxxxxxxx أو +9665xxxxxxxx.</p>
            <label for="member-status">حالة العضوية</label><select id="member-status" class="form-select" name="status"><option value="1" @selected(old('status', $editingUser?->status ?? 1) == 1)>نشط</option><option value="0" @selected(old('status', $editingUser?->status ?? 1) == 0)>موقوف</option></select>
            <fieldset><legend class="fs-6">الأدوار</legend><div class="permission-grid mb-3">@foreach($roles as $role)<label><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $editingUser?->roles->pluck('id')->all() ?? [])))>{{ $role->title ?: $role->name }}</label>@endforeach</div></fieldset>
            <button class="btn btn-primary">حفظ العضوية</button> @if($editingUser)<a class="btn btn-light" href="{{ route('backend.access.index') }}">إلغاء التعديل</a>@endif
        </form>
    </div>
    <div class="card p-4 mb-4"><h4>الأدوار ومستويات الوصول</h4>
        @foreach($roles as $role)<details class="border-bottom py-2"><summary>{{ $role->title ?: $role->name }} <span class="text-muted">• {{ $role->users_count }} عضو • {{ $role->permissions->count() }} صلاحية</span></summary>
            <div class="mb-3">@foreach($role->permissions as $permission)<span class="badge bg-light text-dark">{{ $permission->name }}</span>@endforeach @if($role->name === 'admin')<p>المدير لديه جميع الصلاحيات.</p>@endif</div>
            @if(!$role->is_fixed && !in_array($role->name, ['admin','user','employee','manager']))<div class="d-flex gap-2 mb-3"><a class="btn btn-sm btn-outline-primary" href="{{ route('backend.access.index', ['role' => $role->id]) }}#role-form">تعديل</a><form method="POST" action="{{ route('backend.access.roles.destroy', $role) }}" onsubmit="return confirm('هل تريد حذف هذا الدور؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" @disabled($role->users_count > 0)>حذف الدور</button></form></div>@else<p class="text-muted">دور أساسي محمي</p>@endif
        </details>@endforeach
    </div>
    <div class="card p-4" id="role-form"><h4>{{ $editingRole ? 'تعديل الدور' : 'إضافة دور مخصص' }}</h4><form method="POST" action="{{ $editingRole ? route('backend.access.roles.update', $editingRole) : route('backend.access.roles.store') }}">@csrf @if($editingRole)@method('PUT')@endif
        <label for="role-title">اسم الدور المعروض</label><input id="role-title" class="form-control" name="title" value="{{ old('title', $editingRole?->title) }}" maxlength="100" required>
        <label for="role-name">المعرّف (حروف إنجليزية صغيرة وأرقام وشرطة سفلية)</label><input id="role-name" class="form-control" name="name" dir="ltr" pattern="[a-z][a-z0-9_]{2,49}" value="{{ old('name', $editingRole?->name) }}" required>
        <label for="permission-search">البحث في الصلاحيات</label><input id="permission-search" class="form-control" type="search" placeholder="اكتب اسم الصلاحية">
        <div class="permission-grid mb-3" id="permission-list">@foreach($permissions as $permission)<label><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $editingRole?->permissions->pluck('id')->all() ?? [])))>{{-- {{ $permission->name }} --}} {{ Translation::getTranslatedWords($permission->name) }}</label>@endforeach</div>
        <button class="btn btn-primary">حفظ الدور والصلاحيات</button> @if($editingRole)<a class="btn btn-light" href="{{ route('backend.access.index') }}">إلغاء التعديل</a>@endif
    </form></div>
</div>
<script>document.getElementById('permission-search').addEventListener('input', function () {const term=this.value.toLowerCase();document.querySelectorAll('#permission-list label').forEach(label=>label.hidden=!label.textContent.toLowerCase().includes(term));});</script>
@endsection
