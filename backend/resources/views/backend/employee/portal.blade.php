<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ __('employee_portal.title') }}</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f7f5ef;color:#2b251d;font:16px/1.8 system-ui,sans-serif}header,main{max-width:1200px;margin:auto;padding:22px}header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}nav{display:flex;gap:12px;align-items:center;flex-wrap:wrap}a,button{color:#513b16}a{padding:7px 12px;border-radius:8px;text-decoration:none}a.active{background:#eadbbd;font-weight:bold}button{cursor:pointer;border:1px solid #b8985d;background:#fff;padding:9px 16px;border-radius:9px;font:inherit}h1{font-size:25px;margin:0}h2{font-size:21px}section,.table-wrap{background:white;padding:20px;border:1px solid #e5ddcd;border-radius:14px;margin:18px 0}.filters{display:flex;gap:16px;flex-wrap:wrap;align-items:end}.filters label{display:grid;gap:5px}input{font:inherit;padding:8px;border:1px solid #d7cbb8;border-radius:8px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}th,td{text-align:start;padding:13px;border-bottom:1px solid #ece6db}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:16px}.stats section{margin:0}.stats b{display:block;font-size:26px}.notice{color:#776b58}.pagination{display:flex;list-style:none;gap:8px;padding:0}.notification-alert{background:#a63624;color:white;border-radius:20px;padding:2px 8px}.notification_list{cursor:pointer}details p{border-top:1px solid #eee;padding:8px 0}form{margin:0}
</style></head>
<body>
<header><div><h1>{{ __('employee_portal.title') }}</h1><span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span></div>
<nav><a class="{{ !$report ? 'active' : '' }}" href="{{ route('backend.employee.bookings') }}">{{ __('employee_portal.bookings') }}</a><a class="{{ $report ? 'active' : '' }}" href="{{ route('backend.employee.reports') }}">{{ __('employee_portal.reports') }}</a>
<ul style="display:flex;list-style:none;margin:0;padding:0"><li><details><summary class="notification_list">🔔 {{ __('employee_portal.notifications') }} <span class="notification-alert">{{ auth()->user()->unreadNotifications()->count() }}</span></summary>
@forelse(auth()->user()->notifications()->latest()->limit(5)->get() as $notification)
<p><b>{{ strip_tags($notification->data['subject'] ?? $notification->data['title'] ?? __('employee_portal.notifications')) }}</b><br>{{ strip_tags($notification->data['data']['message'] ?? $notification->data['message'] ?? '') }}</p>
@empty<p>{{ __('employee_portal.no_notifications') }}</p>@endforelse
</details></li></ul>
<form method="post" action="{{ route('logout') }}">@csrf<button>{{ __('employee_portal.logout') }}</button></form></nav></header>
<main><p class="notice">{{ __('employee_portal.notice') }}</p>
<section><form method="get" class="filters"><label>{{ __('employee_portal.from') }}<input type="date" name="from" value="{{ $from }}" required></label><label>{{ __('employee_portal.to') }}<input type="date" name="to" value="{{ $to }}" required></label><button>{{ __('employee_portal.filter') }}</button></form></section>
@if($report)
<h2>{{ __('employee_portal.reports') }}</h2><div class="stats">
@foreach(['bookings'=>'total','completed'=>'completed','cancelled'=>'cancelled','completed_value'=>'value','commission'=>'commission'] as $key=>$label)
<section>{{ __('employee_portal.'.$label) }}<b>{{ in_array($key,['completed_value','commission']) ? number_format($report[$key],2).' '.__('employee_portal.currency') : $report[$key] }}</b></section>
@endforeach</div>
@else
<h2>{{ __('employee_portal.bookings') }}</h2><div class="table-wrap"><table><thead><tr>@foreach(['number','date','customer','service','status','amount'] as $label)<th>{{ __('employee_portal.'.$label) }}</th>@endforeach</tr></thead><tbody>
@forelse($bookings as $booking)<tr><td>#{{ $booking->booking_id }}</td><td>{{ $booking->starts_at }}</td><td>{{ $booking->first_name }} {{ $booking->last_name }}</td><td>{{ $booking->name }}</td><td>{{ trans()->has('employee_portal.'.$booking->status) ? __('employee_portal.'.$booking->status) : $booking->status }}</td><td>{{ number_format($booking->amount,2) }} {{ __('employee_portal.currency') }}</td></tr>
@empty<tr><td colspan="6">{{ __('employee_portal.empty') }}</td></tr>@endforelse
</tbody></table>{{ $bookings->links() }}</div>
@endif
</main>
<script>
function getNotificationCounts(){fetch(@json(route('notification.counts')),{headers:{Accept:'application/json'},cache:'no-store'}).then(r=>r.json()).then(data=>{const badge=document.querySelector('.notification-alert');if(badge){badge.textContent=Number(data.unread_total_count)||0;badge.hidden=!Number(data.unread_total_count)}}).catch(()=>{})}
</script>
@include('backend.includes.realtime-notifications')
</body></html>
