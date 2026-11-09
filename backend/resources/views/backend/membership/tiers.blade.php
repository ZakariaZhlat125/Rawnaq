@extends('backend.layouts.app')

@section('title', 'إدارة فئات العضوية')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">فئات العضوية والاشتراكات</h4>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTierModal">
                        <i class="fas fa-plus"></i> إضافة فئة جديدة
                    </button>
                    @if($tiers->isEmpty())
                    <form action="{{ route('backend.membership.seed') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning text-dark">
                            <i class="fas fa-magic"></i> إنشاء الفئات الافتراضية
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>المستوى</th>
                                <th>الاسم (عربي)</th>
                                <th>الاسم (إنجليزي)</th>
                                <th>النقاط المطلوبة</th>
                                <th>الخصم</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tiers as $tier)
                            <tr>
                                <td>{{ $tier->level }}</td>
                                <td>{{ $tier->name_ar }}</td>
                                <td>{{ $tier->name_en }}</td>
                                <td>{{ $tier->min_points }}</td>
                                <td>{{ $tier->discount_percentage }}%</td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editTierModal{{ $tier->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('backend.membership.tiers.destroy', $tier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editTierModal{{ $tier->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('backend.membership.tiers.update', $tier->id) }}" method="POST" class="modal-content">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">تعديل الفئة: {{ $tier->name_ar }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label>الاسم (عربي)</label>
                                                    <input type="text" name="name_ar" class="form-control" value="{{ $tier->name_ar }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label>الاسم (إنجليزي)</label>
                                                    <input type="text" name="name_en" class="form-control" value="{{ $tier->name_en }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label>النقاط المطلوبة</label>
                                                    <input type="number" name="min_points" class="form-control" value="{{ $tier->min_points }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label>نسبة الخصم (%)</label>
                                                    <input type="number" step="0.01" name="discount_percentage" class="form-control" value="{{ $tier->discount_percentage }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label>مكافأة رصيد عند الترقية (ر.س)</label>
                                                    <input type="number" step="0.01" min="0" name="wallet_bonus" class="form-control" value="{{ $tier->benefits['wallet_bonus'] ?? '' }}">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label>صلاحية المكافأة (أيام، فارغ = بدون انتهاء)</label>
                                                    <input type="number" min="1" name="wallet_bonus_expiry_days" class="form-control" value="{{ $tier->benefits['wallet_bonus_expiry_days'] ?? '' }}">
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label>المزايا (عربي) - كل ميزة في سطر</label>
                                                    <textarea name="benefits_ar" class="form-control" rows="3">{{ implode("\n", $tier->benefits['ar'] ?? []) }}</textarea>
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label>المزايا (إنجليزي) - كل ميزة في سطر</label>
                                                    <textarea name="benefits_en" class="form-control" rows="3">{{ implode("\n", $tier->benefits['en'] ?? []) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">لا توجد فئات حتى الآن.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addTierModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('backend.membership.tiers.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">إضافة فئة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>المستوى (رقم)</label>
                        <input type="number" name="level" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>النقاط المطلوبة</label>
                        <input type="number" name="min_points" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>الاسم (عربي)</label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>الاسم (إنجليزي)</label>
                        <input type="text" name="name_en" class="form-control" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>نسبة الخصم (%)</label>
                        <input type="number" step="0.01" name="discount_percentage" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>مكافأة رصيد عند الترقية (ر.س)</label>
                        <input type="number" step="0.01" min="0" name="wallet_bonus" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>صلاحية المكافأة (أيام، فارغ = بدون انتهاء)</label>
                        <input type="number" min="1" name="wallet_bonus_expiry_days" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>المزايا (عربي) - كل ميزة في سطر</label>
                        <textarea name="benefits_ar" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>المزايا (إنجليزي) - كل ميزة في سطر</label>
                        <textarea name="benefits_en" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>
@endsection
