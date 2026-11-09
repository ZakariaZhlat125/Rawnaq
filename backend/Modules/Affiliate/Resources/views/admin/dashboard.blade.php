@extends('backend.layouts.app')

@section('title', __('affiliate.admin_dashboard_title'))

@section('content')
<style>
    .affiliate-settings-form .form-label {
        margin-bottom: 8px;
        font-weight: 600;
    }

    .affiliate-settings-form .form-control {
        padding: 12px 14px;
        min-height: 48px;
        border-radius: 10px;
    }

    .affiliate-settings-form .btn {
        padding: 11px 20px;
        border-radius: 10px;
        font-weight: 600;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card-custom text-center">
                <div class="card-body">
                    <h5>{{ __('affiliate.total_affiliates') }}</h5>
                    <h3>{{ $totalAffiliates }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card-custom text-center">
                <div class="card-body">
                    <h5>{{ __('affiliate.total_visitors') }}</h5>
                    <h3>{{ $totalVisitors }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card-custom text-center">
                <div class="card-body">
                    <h5>{{ __('affiliate.total_conversions') }}</h5>
                    <h3>{{ $totalConversions }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card card-custom text-center">
                <div class="card-body">
                    <h5>{{ __('affiliate.total_earnings') }}</h5>
                    <h3>{{ \Currency::format($totalEarnings) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('affiliate.referral_settings') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('affiliate.settings.update') }}" method="POST" class="affiliate-settings-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('affiliate.commission_calc_type') }}</label>
                                <select class="form-control" name="commission_calc_type" required>
                                    <option value="fixed" @selected($commissionCalcType === 'fixed')>{{ __('affiliate.fixed') }}</option>
                                    <option value="percentage" @selected($commissionCalcType === 'percentage')>{{ __('affiliate.percentage') }}</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('affiliate.commission_value') }}</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control"
                                    name="commission_value"
                                    value="{{ old('commission_value', $commissionValue) }}"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('affiliate.commission_apply_type') }}</label>
                                <select class="form-control" name="commission_apply_type" required>
                                    <option value="first_purchase" @selected($commissionApplyType === 'first_purchase')>{{ __('affiliate.first_purchase') }}</option>
                                    <option value="recurring" @selected($commissionApplyType === 'recurring')>{{ __('affiliate.recurring') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">{{ __('affiliate.save_settings') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header">
                    <h5>{{ __('affiliate.top_affiliates') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('affiliate.name') }}</th>
                            <th>{{ __('affiliate.email') }}</th>
                            <th>{{ __('affiliate.ref_code') }}</th>
                            <th>{{ __('affiliate.conversions') }}</th>
                            <th>{{ __('affiliate.wallet_total') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($topAffiliates as $affiliate)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $affiliate->user->full_name ?? $affiliate->user->first_name }}</td>
                                <td>{{ $affiliate->user->email }}</td>
                                <td>{{ $affiliate->ref_code }}</td>
                                <td>{{ $affiliate->conversions_count }}</td>
                                <td>{{ \Currency::format($affiliate->wallet_total) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
