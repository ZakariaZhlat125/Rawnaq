@extends('backend.layouts.app')

@section('title', 'أعضاء البرنامج')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">أعضاء برنامج الولاء</h4>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form method="GET" class="row mb-4">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="بحث بالاسم أو رقم الجوال" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <select name="tier_id" class="form-control">
                            <option value="">كل الفئات</option>
                            @foreach($tiers as $tier)
                                <option value="{{ $tier->id }}" {{ request('tier_id') == $tier->id ? 'selected' : '' }}>{{ $tier->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                        <a href="{{ route('backend.membership.members.index') }}" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>رقم الجوال</th>
                                <th>الفئة الحالية</th>
                                <th>رصيد النقاط (للاسترداد)</th>
                                <th>إجمالي النقاط المكتسبة</th>
                                <th>حالة العضوية</th>
                                <th>تاريخ الانضمام</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($memberships as $membership)
                            <tr>
                                <td>{{ $membership->user->full_name ?? '-' }}</td>
                                <td>{{ $membership->user->mobile ?? '-' }}</td>
                                <td><span class="badge bg-primary">{{ $membership->tier->name_ar }}</span></td>
                                <td>{{ $membership->points_balance }}</td>
                                <td>{{ $membership->total_points_earned }}</td>
                                <td>
                                    @if($membership->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                                <td>{{ $membership->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#addPointsModal{{ $membership->user_id }}">
                                        <i class="fas fa-coins"></i> إضافة نقاط
                                    </button>
                                </td>
                            </tr>

                            <!-- Add Points Modal -->
                            <div class="modal fade" id="addPointsModal{{ $membership->user_id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('backend.membership.add-points') }}" method="POST" class="modal-content">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">إضافة نقاط: {{ $membership->user->full_name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="{{ $membership->user_id }}">
                                            <div class="mb-3">
                                                <label>عدد النقاط المراد إضافتها</label>
                                                <input type="number" name="points" class="form-control" min="1" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>السبب (اختياري)</label>
                                                <input type="text" name="reason" class="form-control" placeholder="مثال: تعويض، مكافأة خاصة...">
                                            </div>
                                            <p class="text-muted small">ملاحظة: إضافة النقاط قد تؤدي إلى ترقية العميل لفئة أعلى تلقائياً وسيتم إشعاره عبر SMS.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">إضافة</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">لا يوجد أعضاء مطابقين للبحث.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    {{ $memberships->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
