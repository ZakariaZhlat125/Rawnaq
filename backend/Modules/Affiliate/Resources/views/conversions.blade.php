@extends('affiliate::layouts.master')

@section('title', 'التحويلات')

@section('content')

<div class="card">
    <div class="card-header">
        <h5>قائمة التحويلات</h5>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>رقم الطلب</th>
                    <th>العميل</th>
                    <th>المبلغ المكتسب</th>
                    <th>الحالة</th>
                    <th>تاريخ التحويل</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($conversions as $conv)
                <tr>
                    <td>{{ $conv->id }}</td>
                    <td>{{ $conv->order_id }}</td>
                    <td>{{ $conv->user->name ?? '-' }}</td>
                    <td>${{ number_format($conv->commission, 2) }}</td>
                    <td>{{ $conv->status }}</td>
                    <td>{{ $conv->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">لا يوجد تحويلات</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{ $conversions->links() }}
    </div>
</div>

@endsection
