@extends('backend.layouts.app')

@section('title')
    آراء العملاء
@endsection

@push('after-styles')
<style>
    /* ========== Page Header ========== */
    .reviews-page-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 28px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    }
    .reviews-page-header h2 {
        color: #BF9456;
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
    }
    .reviews-page-header p {
        color: rgba(255,255,255,0.7);
        margin: 4px 0 0;
        font-size: 0.9rem;
    }

    /* ========== Stats Cards ========== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }
    .stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px 24px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        border: 1px solid #f0ece4;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .stat-icon.total { background: #e8f0fe; color: #1a73e8; }
    .stat-icon.approved { background: #e8f5e9; color: #2e7d32; }
    .stat-icon.pending { background: #fff3e0; color: #e65100; }
    .stat-icon.rating { background: #fdf2e0; color: #BF9456; }
    .stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1;
    }
    .stat-label {
        font-size: 0.8rem;
        color: #888;
        margin-top: 2px;
    }

    /* ========== Filter Bar ========== */
    .filter-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .filter-btn {
        padding: 8px 20px;
        border-radius: 25px;
        border: 1.5px solid #e0dcd4;
        background: #fff;
        color: #666;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
    }
    .filter-btn:hover, .filter-btn.active {
        background: #BF9456;
        color: #fff;
        border-color: #BF9456;
    }

    /* ========== Reviews Table ========== */
    .reviews-table-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #f0ece4;
    }
    .reviews-table {
        width: 100%;
        border-collapse: collapse;
    }
    .reviews-table thead th {
        background: #faf8f5;
        padding: 14px 18px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #666;
        text-align: right;
        border-bottom: 2px solid #f0ece4;
    }
    .reviews-table tbody tr {
        border-bottom: 1px solid #f5f3ef;
        transition: background 0.2s;
    }
    .reviews-table tbody tr:hover {
        background: #fdfcfa;
    }
    .reviews-table tbody td {
        padding: 16px 18px;
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .reviews-table tbody tr:last-child {
        border-bottom: none;
    }

    /* User Info */
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #f0ece4;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #BF9456;
        font-size: 1rem;
        flex-shrink: 0;
        overflow: hidden;
    }
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .user-name {
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
    }
    .user-phone {
        font-size: 0.75rem;
        color: #999;
    }

    /* Stars */
    .stars-display {
        display: flex;
        gap: 2px;
    }
    .stars-display i {
        font-size: 0.9rem;
    }
    .stars-display .filled { color: #f5a623; }
    .stars-display .empty { color: #ddd; }

    /* Review Text */
    .review-text {
        max-width: 300px;
        line-height: 1.5;
        color: #555;
    }
    .review-text.truncated {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Badges */
    .approval-badge {
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .approval-badge.approved {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .approval-badge.pending {
        background: #fff3e0;
        color: #e65100;
    }

    /* Actions */
    .action-btns {
        display: flex;
        gap: 6px;
    }
    .action-btn {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.25s;
        font-size: 0.8rem;
    }
    .action-btn.approve { background: #e8f5e9; color: #2e7d32; }
    .action-btn.approve:hover { background: #2e7d32; color: #fff; }
    .action-btn.approve.active { background: #2e7d32; color: #fff; }
    .action-btn.hide-btn { background: #fff3e0; color: #e65100; }
    .action-btn.hide-btn:hover { background: #e65100; color: #fff; }
    .action-btn.delete { background: #fce4ec; color: #c62828; }
    .action-btn.delete:hover { background: #c62828; color: #fff; }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .empty-state i {
        font-size: 4rem;
        color: #d0cbc2;
        margin-bottom: 16px;
    }
    .empty-state h4 {
        color: #666;
        font-weight: 600;
        margin-bottom: 8px;
    }

    /* Booking Info */
    .booking-info {
        font-size: 0.8rem;
        color: #888;
    }
    .booking-id {
        font-weight: 600;
        color: #BF9456;
    }

    /* Alert */
    .alert-custom {
        padding: 14px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        animation: slideDown 0.35s ease;
    }
    .alert-custom.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .reviews-table { display: block; overflow-x: auto; }
    }
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
    @hasPermission('overall_booking_report')
        <a class="btn btn-outline-primary mb-3" href="{{ route('backend.reports.reviews') }}">تقرير تقييمات الفروع والخدمات والموظفين</a>
    @endhasPermission
    {{-- Page Header --}}
    <div class="reviews-page-header">
        <h2><i class="fas fa-star" style="margin-left: 8px;"></i> آراء وتقييمات العملاء</h2>
        <p>إدارة واعتماد تقييمات العملاء بعد إكمال الحجز</p>
    </div>

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total"><i class="fas fa-comments"></i></div>
            <div>
                <div class="stat-value">{{ $totalReviews }}</div>
                <div class="stat-label">إجمالي التقييمات</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon approved"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="stat-value">{{ $approvedCount }}</div>
                <div class="stat-label">معتمدة</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
            <div>
                <div class="stat-value">{{ $pendingCount }}</div>
                <div class="stat-label">بانتظار الاعتماد</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rating"><i class="fas fa-star"></i></div>
            <div>
                <div class="stat-value">{{ $avgRating ? number_format($avgRating, 1) : '0' }}</div>
                <div class="stat-label">متوسط التقييم</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <a href="{{ route('app.booking-reviews') }}" class="filter-btn {{ !request('filter') ? 'active' : '' }}">
            الكل ({{ $totalReviews }})
        </a>
        <a href="{{ route('app.booking-reviews', ['filter' => 'approved']) }}" class="filter-btn {{ request('filter') == 'approved' ? 'active' : '' }}">
            <i class="fas fa-check-circle"></i> معتمدة ({{ $approvedCount }})
        </a>
        <a href="{{ route('app.booking-reviews', ['filter' => 'pending']) }}" class="filter-btn {{ request('filter') == 'pending' ? 'active' : '' }}">
            <i class="fas fa-clock"></i> بانتظار الاعتماد ({{ $pendingCount }})
        </a>
    </div>

    {{-- Reviews Table --}}
    @if($reviews->count() > 0)
        <div class="reviews-table-card">
            <table class="reviews-table">
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>التقييم</th>
                        <th>الرأي</th>
                        <th>الحجز</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviews as $review)
                        <tr id="review-row-{{ $review->id }}">
                            {{-- User --}}
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        @if($review->user && $review->user->profile_image)
                                            <img src="{{ asset($review->user->profile_image) }}" alt="">
                                        @else
                                            {{ $review->user ? mb_substr($review->user->first_name ?? '', 0, 1) : '?' }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="user-name">{{ $review->user->full_name ?? 'مستخدم محذوف' }}</div>
                                        <div class="user-phone">{{ $review->user->mobile ?? '' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Rating --}}
                            <td>
                                <div class="stars-display">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa-solid fa-star {{ $i <= $review->rating ? 'filled' : 'empty' }}"></i>
                                    @endfor
                                </div>
                            </td>

                            {{-- Detailed booking ratings --}}
                            @if($review->branch_rating)
                                <td>
                                    <div>الفرع: {{ \App\Services\ReviewReport::name($review->booking?->branch?->name) }} — {{ $review->branch_rating }} / 5</div>
                                    @foreach($review->service_ratings ?? [] as $rating)
                                        @php($ratedService = $review->booking?->services->firstWhere('id', $rating['booking_service_id']))
                                        <div>{{ $ratedService?->service_name ?? ('خدمة #' . $rating['service_id']) }}: {{ $rating['service_rating'] }} / 5</div>
                                        @if($rating['employee_rating'] ?? null)
                                            <div>{{ $ratedService?->employee?->full_name ?? 'الموظف' }}: {{ $rating['employee_rating'] }} / 5</div>
                                        @endif
                                    @endforeach
                                    <div class="review-text">{{ $review->review_text ?? '-' }}</div>
                                </td>
                            @else
                            {{-- Review Text --}}
                            <td>
                                <div class="review-text truncated" title="{{ $review->review_text }}">
                                    {{ $review->review_text ?? '-' }}
                                </div>
                            </td>

                            @endif
                            {{-- Booking Info --}}
                            <td>
                                <div class="booking-info">
                                    <span class="booking-id">#{{ $review->booking_id }}</span>
                                </div>
                            </td>

                            {{-- Date --}}
                            <td>
                                <div class="booking-info">
                                    {{ $review->created_at->format('Y/m/d') }}<br>
                                    <small>{{ $review->created_at->diffForHumans() }}</small>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td>
                                <span class="approval-badge {{ $review->is_approved ? 'approved' : 'pending' }}" id="badge-{{ $review->id }}">
                                    {{ $review->is_approved ? 'معتمد' : 'بانتظار الاعتماد' }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn approve {{ $review->is_approved ? 'active' : '' }}"
                                            title="{{ $review->is_approved ? 'إلغاء الاعتماد' : 'اعتماد' }}"
                                            onclick="toggleApproval({{ $review->id }})">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="action-btn delete"
                                            title="حذف"
                                            onclick="deleteReview({{ $review->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <i class="fas fa-star"></i>
            <h4>لا توجد تقييمات حالياً</h4>
            <p>ستظهر التقييمات هنا عندما يقوم العملاء بتقييم حجوزاتهم</p>
        </div>
    @endif
@endsection

@push('after-scripts')
<script>
    function toggleApproval(id) {
        fetch(@json(route('booking-reviews.approve', '__ID__')).replace('__ID__', encodeURIComponent(id)), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById(`badge-${id}`);
                const btn = document.querySelector(`#review-row-${id} .action-btn.approve`);

                if (data.is_approved) {
                    badge.className = 'approval-badge approved';
                    badge.textContent = 'معتمد';
                    btn.classList.add('active');
                    btn.title = 'إلغاء الاعتماد';
                } else {
                    badge.className = 'approval-badge pending';
                    badge.textContent = 'بانتظار الاعتماد';
                    btn.classList.remove('active');
                    btn.title = 'اعتماد';
                }
            }
        })
        .catch(err => console.error(err));
    }

    function deleteReview(id) {
        if (!confirm('هل أنت متأكد من حذف هذا التقييم؟')) return;

        fetch(@json(route('booking-reviews.destroy', '__ID__')).replace('__ID__', encodeURIComponent(id)), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`review-row-${id}`);
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    if (document.querySelectorAll('.reviews-table tbody tr').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        })
        .catch(err => console.error(err));
    }
</script>
@endpush
