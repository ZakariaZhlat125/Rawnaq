@hasPermission('menu_builder_sidebar')
<div class="sidebar-base pr-hide
            {{ getCustomizationSetting('sidebar_show') == 'sidebar-none' ? 'sidebar-none' : 'sidebar' }}
            {{ !empty(getCustomizationSetting('sidebar_menu_style')) ? getCustomizationSetting('sidebar_menu_style') : 'left-bordered' }}
            {{ getCustomizationSetting('sidebar_color') }}
            {{ !empty(getCustomizationSetting('sidebar_type')) ? implode(' ',getCustomizationSetting('sidebar_type')) : '' }}
            "
            data-toggle="main-sidebar" id="sidebar" data-sidebar="responsive">
    <div class="d-flex align-items-center justify-content-start">
        <div class="logo-main">
            <a href="{{route('backend.dashboard')}}" class="navbar-brand" >
                <img class="logo-normal img-fluid" src="{{ asset('images/samilogo.png') }}" height="30" alt="{{ app_name() }}">
                <img class="logo-normal dark-normal img-fluid" src="{{ asset('images/samilogo.png') }}" height="30" alt="{{ app_name() }}">
                <img class="logo-mini img-fluid" src="{{ asset('images/samilogo.png') }}" height="30" alt="{{ app_name() }}">
                <img class="logo-mini dark-mini img-fluid" src="{{ asset('images/samilogo.png') }}" height="30" alt="{{ app_name() }}">
            </a>
        </div>
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="icon">
                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.25 12.2744L19.25 12.2744" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M10.2998 18.2988L4.2498 12.2748L10.2998 6.24976" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </i>
        </div>
    </div>
    <div class="sidebar-body pt-0 data-scrollbar">
        <div class="sidebar-list" id="sidebar">
            <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
                @can('view_loyalty')
                <li class="nav-item"><a href="{{ route('backend.service-repeat.index') }}" class="nav-link {{ request()->routeIs('backend.service-repeat.*') ? 'active' : '' }}"><i class="fa-solid fa-gift"></i><span class="item-name">{{ __('service_repeat.title') }}</span></a></li>
                <li class="nav-item"><a href="{{ route('backend.quiet-hours.index') }}" class="nav-link {{ request()->routeIs('backend.quiet-hours.*') ? 'active' : '' }}"><i class="fa-solid fa-clock"></i><span class="item-name">{{ __('quiet_hours.title') }}</span></a></li>
                @endcan
                @role('admin')
                <li class="nav-item"><a href="{{ route('backend.access.index') }}" class="nav-link {{ request()->routeIs('backend.access.*') ? 'active' : '' }}"><i class="fa-solid fa-user-shield"></i><span class="item-name">الأدوار والصلاحيات</span></a></li>
                @endrole
@php
    $menu = new \App\Http\Middleware\GenerateMenus();
    $menu = $menu->handle('menu', 'vertical', 'ARRAY_MENU');
    if (auth()->user()->hasRole('admin') || auth()->user()->can('overall_booking_report')) {
        $customerReportItem = $menu->add('<span class="item-name">'.e(__('customer_activity.title')).'</span>', [
            'route' => 'backend.reports.customer-activity',
            'class' => 'nav-item',
        ])->data('order', 19)->prepend('<i class="fa-solid fa-users"></i> ');
        $customerReportItem->link->attr(['class' => 'nav-link']);
        if (request()->routeIs('backend.reports.customer-activity')) {
            $customerReportItem->active();
            $customerReportItem->link->active();
        }
        $referralReportItem = $menu->add('<span class="item-name">تقرير إحالات التسجيل</span>', [
            'route' => 'backend.reports.referrals', 'class' => 'nav-item',
        ])->data('order', 19)->prepend('<i class="fa-solid fa-user-plus"></i> ');
        $referralReportItem->link->attr(['class' => 'nav-link']);
        if (request()->routeIs('backend.reports.referrals')) {
            $referralReportItem->active();
            $referralReportItem->link->active();
        }
        $reviewReportItem = $menu->add('<span class="item-name">تقرير التقييمات</span>', [
            'route' => 'backend.reports.reviews', 'class' => 'nav-item',
        ])->data('order', 19)->data('keep_visible', true)->prepend('<i class="fa-solid fa-star"></i> ');
        $reviewReportItem->link->attr(['class' => 'nav-link']);
        if (request()->routeIs('backend.reports.reviews')) {
            $reviewReportItem->active();
            $reviewReportItem->link->active();
        }
        $menu = $menu->sortBy('order');
    }
    
    if (auth()->user()->hasRole('admin') || auth()->user()->can('view_loyalty')) {
        $loyaltyReportItem = $menu->add('<span class="item-name">تقرير نقاط الولاء</span>', ['route'=>'backend.reports.loyalty','class'=>'nav-item'])
            ->data('order',19)->prepend('<i class="fa-solid fa-coins"></i> ');
        $loyaltyReportItem->link->attr(['class'=>'nav-link']);
        if(request()->routeIs('backend.reports.loyalty')) { $loyaltyReportItem->active(); $loyaltyReportItem->link->active(); }
        $menu=$menu->sortBy('order');
    }
    if (auth()->user()->hasRole('admin') || auth()->user()->can('view_financial_report')) {
        $hasRevenueLink = collect($menu->all())->contains(fn($item) => $item->link && $item->link->href === route('backend.reports.financial-report'));
        if (!$hasRevenueLink) {
            $revenueItem=$menu->add('<span class="item-name">تقرير الإيرادات</span>', ['route'=>'backend.reports.financial-report','class'=>'nav-item'])->data('order',19)->prepend('<i class="fa-solid fa-chart-line"></i> ');
            $revenueItem->link->attr(['class'=>'nav-link']);
            if(request()->routeIs('backend.reports.financial-report')) { $revenueItem->active(); $revenueItem->link->active(); }
            $menu=$menu->sortBy('order');
        }
    }
    $filteredItems = $menu->roots()->filter(function($item) {
        if ($item->data('keep_visible')) return true;
        $hiddenItems = [
            'staff_earnings', 'أرباح', 'earnings',
            'staffs_payouts', 'مدفوعات', 'payouts',
            'reviews', 'التقييمات', 'review'
        ];

        foreach ($hiddenItems as $hidden) {
            if (str_contains(strtolower($item->title), strtolower($hidden))) {
                return false;
            }
        }
        return true;
    });
@endphp

@include(config('laravel-menu.views.bootstrap-items'), ['items' => $filteredItems])
                <li class="nav-item static-item mt-3 pt-3 border-top">
                    <div class="nav-link static-item" role="heading" aria-level="2" style="cursor:default">
                        <span class="default-icon fw-bold" style="letter-spacing:normal;white-space:normal">{{ app()->getLocale() === 'ar' ? 'صفحات وخدمات الموقع' : 'Website pages and services' }}</span>
                        <span class="mini-icon" aria-hidden="true">•••</span>
                    </div>
                </li>
                
                <li class="nav-item">
    <a
        href="{{ route('backend.page-qr.index') }}"
        class="nav-link {{ request()->routeIs('backend.page-qr.*') ? 'active' : '' }}"
    >
        <i class="fa fa-qrcode"></i>
        <span class="item-name">{{app()->getLocale()=='ar' ? 'QR صفحات الموقع' : 'website pages qr code'}}</span>
    </a>
</li>

<li class="nav-item">
    <a
        href="{{ route('backend.dynamic-qr.index') }}"
        class="nav-link {{ request()->routeIs('backend.dynamic-qr.*') ? 'active' : '' }}"
    >
        <i class="fa fa-qrcode"></i>
        <span class="item-name">
            {{app()->getLocale()=='ar' ? ' إدارة QR' : 'manage qr'}}
           </span>
    </a>
</li>



                @if (auth()->user()->canAny(['view_gift', 'redeem_gift_branch', 'manage_gift_batches', 'manage_gift_designs', 'view_gift_reports']))
                @php
                    $giftHome = auth()->user()->can('view_gift') ? route('app.gift')
                        : (auth()->user()->can('redeem_gift_branch') ? route('gifts.redeem')
                        : (auth()->user()->can('view_gift_reports') ? route('gifts.report')
                        : (auth()->user()->can('manage_gift_batches') ? route('gifts.batches') : route('gift-designs.index'))));
                    $giftActive = request()->routeIs('app.gift', 'gifts.*', 'gift-designs.*');
                @endphp
                <li class="nav-item {{ $giftActive ? 'active' : '' }}">
                    <a href="{{ $giftHome }}" class="nav-link {{ $giftActive ? 'active' : '' }}">
                        <i class="fa fa-gift"></i>
                        <span class="item-name">{{ __('messagess.gifts') }}</span>
                    </a>
                </li>
                @endif
                
                @hasPermission('view_Lucky_Wheel')
                <li class="nav-item {{ request()->routeIs('app.Wheel') ? 'active' : '' }}">
                    <a href="{{ route('app.Wheel') }}" class="nav-link {{ request()->routeIs('app.Wheel') ? 'active' : '' }}">
                    <svg id="wheel-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" width="15" height="20">
                      <title>{{ __('Lucky Wheel') }}</title>
                      <rect width="100%" height="100%" fill="transparent"/>
                      <g id="wheel-group" transform="translate(0,0)">
                        <path d="M250,250 L250,0 A250,250 0 0,1 375,33.494 Z" fill="#6c757d"/>
                        <path d="M250,250 L375,33.494 A250,250 0 0,1 466.506,125 Z" fill="#ffffff"/>
                        <path d="M250,250 L466.506,125 A250,250 0 0,1 500,250 Z" fill="#6c757d"/>
                        <path d="M250,250 L500,250 A250,250 0 0,1 466.506,375 Z" fill="#ffffff"/>
                        <path d="M250,250 L466.506,375 A250,250 0 0,1 375,466.506 Z" fill="#6c757d"/>
                        <path d="M250,250 L375,466.506 A250,250 0 0,1 250,500 Z" fill="#ffffff"/>
                        <path d="M250,250 L250,500 A250,250 0 0,1 125,466.506 Z" fill="#6c757d"/>
                        <path d="M250,250 L125,466.506 A250,250 0 0,1 33.494,375 Z" fill="#ffffff"/>
                        <path d="M250,250 L33.494,375 A250,250 0 0,1 0,250 Z" fill="#6c757d"/>
                        <path d="M250,250 L0,250 A250,250 0 0,1 33.494,125 Z" fill="#ffffff"/>
                        <path d="M250,250 L33.494,125 A250,250 0 0,1 125,33.494 Z" fill="#6c757d"/>
                        <path d="M250,250 L125,33.494 A250,250 0 0,1 250,0 Z" fill="#ffffff"/>
                        <circle cx="250" cy="250" r="52" fill="#fff" stroke="#000" stroke-width="4"/>
                        <text x="250" y="250" text-anchor="middle" dominant-baseline="central" font-size="16" font-weight="700" fill="#000">SPIN</text>
                      </g>
                      <circle cx="250" cy="250" r="248" fill="none" stroke="#000" stroke-width="4"/>
                    </svg>

                        <span class="item-name">{{ __('wheel.WheelOfFortune') }}</span>
                    </a>
                </li>
                @endhasPermission


                @hasPermission('view_loyalty')
                <li class="nav-item {{ request()->routeIs('app.loyalty') ? 'active' : '' }}">
                    <a href="{{ route('app.loyalty') }}" class="nav-link {{ request()->routeIs('app.loyalty') ? 'active' : '' }}">
                        <i class="fas fa-coins"></i>
                        <span class="item-name">{{ __('messages.loyalty_management') }}</span>
                    </a>
                </li>
                @endhasPermission

                @if (auth()->user()->canAny(['view_wallet_admin', 'manage_wallet_withdrawals', 'manage_wallet_offers', 'manage_wallet_policy']))
                @php
                    $walletHome = auth()->user()->can('view_wallet_admin') ? route('wallet.index')
                        : (auth()->user()->can('manage_wallet_withdrawals') ? route('wallet.withdrawals')
                        : (auth()->user()->can('manage_wallet_offers') ? route('wallet.offers') : route('wallet.policy')));
                    $walletActive = request()->routeIs('wallet.index', 'wallet.withdrawals', 'wallet.offers', 'wallet.policy');
                @endphp
                <li class="nav-item {{ $walletActive ? 'active' : '' }}">
                    <a href="{{ $walletHome }}" class="nav-link {{ $walletActive ? 'active' : '' }}">
                        <i class="fa-solid fa-wallet"></i>
                        <span class="item-name">{{ __('wallet_ledger.admin.title') }}</span>
                    </a>
                </li>
                @endif

                @can('manage_payment_gateways')
                <li class="nav-item {{ request()->routeIs('backend.payment-gateways.*') ? 'active' : '' }}">
                    <a href="{{ route('backend.payment-gateways.index') }}" class="nav-link {{ request()->routeIs('backend.payment-gateways.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-credit-card"></i>
                        <span class="item-name">{{ __('payment_gateways.title') }}</span>
                    </a>
                </li>
                @endcan

                @hasPermission('view_ads')
                <li class="nav-item {{ request()->routeIs('app.ads') ? 'active' : '' }}">
                    <a href="{{ route('app.ads') }}" class="nav-link {{ request()->routeIs('app.ads') ? 'active' : '' }}">
                        <i class="fa-solid fa-ad"></i>
                        <span class="item-name">{{ __('messagess.Ads') }}</span>
                    </a>
                </li>
                @endhasPermission

                @hasPermission('edit_booking')
                <li class="nav-item"><a href="{{ route('backend.booking-arrival.index') }}" class="nav-link"><i class="fas fa-qrcode"></i><span class="item-name">تأكيد وصول العملاء</span></a></li>
                @endhasPermission
                @role('admin')
                <li class="nav-item"><a href="{{ route('backend.customer-messages.index') }}" class="nav-link"><i class="fab fa-whatsapp"></i><span class="item-name">رسائل العملاء والمناسبات</span></a></li>
                @endrole
                <li class="nav-item {{ request()->routeIs('app.offer-banners') ? 'active' : '' }}">
                    <a href="{{ route('app.offer-banners') }}" class="nav-link {{ request()->routeIs('app.offer-banners') ? 'active' : '' }}">
                        <i class="fas fa-tags"></i>
                        <span class="item-name">بنرات العروض</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('app.booking-reviews') ? 'active' : '' }}">
                    <a href="{{ route('app.booking-reviews') }}" class="nav-link {{ request()->routeIs('app.booking-reviews') ? 'active' : '' }}">
                        <i class="fas fa-star-half-alt"></i>
                        <span class="item-name">آراء العملاء</span>
                    </a>
                </li>

                @hasPermission('view_reject_reasons')
                <li class="nav-item {{ request()->routeIs('app.reject') ? 'active' : '' }}">
                    <a href="{{ route('app.reject') }}" class="nav-link {{ request()->routeIs('app.reject') ? 'active' : '' }}">
                        <i class="fa-solid fa-ban"></i>
                        <span class="item-name">{{ __('messagess.cancellation_of_reservation') }}</span>
                    </a>
                </li>
                @endhasPermission

                @hasPermission('view_terms_and_conditions')
                <li class="nav-item"><a href="{{ route('backend.home-page-content.edit') }}" class="nav-link"><i class="fas fa-home"></i><span class="item-name">محتوى الرئيسية</span></a></li>
                <li class="nav-item"><a href="{{ route('backend.faq.edit') }}" class="nav-link {{ request()->routeIs('backend.faq.*') ? 'active' : '' }}"><i class="fas fa-circle-question"></i><span class="item-name">{{ __('faq.title') }}</span></a></li>
                <li class="nav-item {{ request()->routeIs('backend.frontend-seo.*') ? 'active' : '' }}">
                    <a href="{{ route('backend.frontend-seo.edit') }}" class="nav-link"><i class="fas fa-search"></i><span class="item-name">إعدادات SEO</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('backend.about-page.*') ? 'active' : '' }}">
                    <a href="{{ route('backend.about-page.edit') }}" class="nav-link"><i class="fas fa-info-circle"></i><span class="item-name">الصفحة التعريفية</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('backend.home-service-page.*') ? 'active' : '' }}">
                    <a href="{{ route('backend.home-service-page.edit') }}" class="nav-link {{ request()->routeIs('backend.home-service-page.*') ? 'active' : '' }}">
                        <i class="fas fa-home"></i>
                        <span class="item-name">صفحة الخدمة المنزلية</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('backend.cafe-page.*') ? 'active' : '' }}">
                    <a href="{{ route('backend.cafe-page.edit') }}" class="nav-link {{ request()->routeIs('backend.cafe-page.*') ? 'active' : '' }}">
                        <i class="fas fa-home"></i>
                        <span class="item-name">صفحة المقهى التعريفية</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('app.TermsAndConditions') ? 'active' : '' }}">
                    <a href="{{ route('app.TermsAndConditions') }}" class="nav-link {{ request()->routeIs('app.TermsAndConditions') ? 'active' : '' }}">
                        <i class="fas fa-file-contract"></i>
                        <span class="item-name">{{ __('messages.TermsAndConditions') }}</span>
                    </a>
                </li>
                @endhasPermission

                @hasPermission('view_sms')
                <!--<li class="nav-item {{ request()->routeIs('app.sms') ? 'active' : '' }}">-->
                <!--    <a href="{{ route('app.sms') }}" class="nav-link {{ request()->routeIs('app.sms') ? 'active' : '' }}">-->
                <!--        <i class="fas fa-sms"></i>-->
                <!--        <span class="item-name">{{ __('messages.sms') }}</span>-->
                <!--    </a>-->
                <!--</li>-->
                @endhasPermission

            </ul>
        </div>
    </div>
    <div class="sidebar-footer"></div>
</div>
@endhasPermission
