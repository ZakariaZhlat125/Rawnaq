<?php

use App\Http\Controllers\Backend\AdsController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\CafeCategoryController;
use App\Http\Controllers\Backend\CafeItemController;
use App\Http\Controllers\Backend\CafeOrderController;
use App\Http\Controllers\Backend\CafeTableController;
use App\Http\Controllers\Backend\CenterInfoController;
use App\Http\Controllers\BookingsController;
use App\Http\Controllers\BookingCartController;
use App\Http\Controllers\CafeFrontendController;
use App\Http\Controllers\CenterInfoPageController;
use App\Http\Controllers\Backend\ContactMessageController;
use App\Http\Controllers\GiftCardController;
use App\Http\Controllers\Backend\GiftController;
use App\Http\Controllers\HomeBookingController;
use App\Http\Controllers\Backend\InvoiceController;
use App\Http\Controllers\Api\CouponValidationController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Backend\LoyaltyController;
use App\Http\Controllers\FrontendLoyaltyController;
use App\Http\Controllers\Backend\ModuleController;
use App\Http\Controllers\Backend\offersController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Backend\PermissionController;
use App\Http\Controllers\Backend\ReportsController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\RolePermission;
use App\Http\Controllers\Backend\RejectController;
use App\Http\Controllers\Backend\SearchController;
use App\Http\Controllers\PackageDetailsController;
use App\Http\Controllers\SignController;
use App\Http\Controllers\Backend\TermsAndConditionsController;
use App\Http\Controllers\Backend\TaqnyatSmsController;
use App\Http\Controllers\Backend\WheelController;
use App\Http\Controllers\Backend\OfferBannerController;
use App\Http\Controllers\Backend\BookingReviewController;
use App\Http\Controllers\BookingReviewFrontendController;

use App\Http\Controllers\Backend\BackendController;
use App\Http\Controllers\Backend\BackupController;
use App\Http\Controllers\Backend\BranchController;
use App\Http\Controllers\Backend\NotificationsController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\UsersController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::prefix('app/access')->name('backend.access.')->middleware(['auth', 'role:admin'])->controller(\App\Http\Controllers\Backend\AccessController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/members', 'saveMember')->name('members.store');
    Route::put('/members/{member}', 'saveMember')->name('members.update');
    Route::post('/roles', 'saveRole')->name('roles.store');
    Route::put('/roles/{role}', 'saveRole')->name('roles.update');
    Route::delete('/roles/{role}', 'deleteRole')->name('roles.destroy');
});

use Modules\Affiliate\Http\Controllers\AffiliateAdminController;
use Modules\Employee\Http\Controllers\Backend\EmployeesController;

use App\Providers\RouteServiceProvider;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('test', function () {
    $branchId = 32;

    $categories = [
        'hair' => 'العناية بالشعر',
        'body' => 'العناية بالجسم',
        'beard' => 'العناية باللحية',
        'skin' => 'العناية بالبشرة',
        'perf' => 'العطور',
        'sets' => 'باقات ومجموعات',
    ];

    $categoryIds = [];

    foreach ($categories as $slug => $name) {
        $categoryIds[$slug] = ProductCategory::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'status' => 1]
        )->id;
    }

    $products = [
        [
            'name' => 'طقم العناية الفاخر',
            'slug' => 'luxury-care-set',
            'short_description' => 'طقم متكامل للعناية اليومية بالبشرة والشعر',
            'image' => '/products/care-set-card-hq.png',
            'min_price' => 145,
            'max_price' => 145,
            'category' => 'sets',
        ],
        [
            'name' => 'طقم الترطيب المكثف',
            'slug' => 'moisturizing-set',
            'short_description' => 'مجموعة ترطيب مكثف للبشرة الجافة',
            'image' => '/products/moisturizing-set-hq.png',
            'min_price' => 120,
            'max_price' => 120,
            'category' => 'skin',
        ],
    ];

    foreach ($products as $data) {
        $product = Product::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'short_description' => $data['short_description'],
                'image' => $data['image'],
                'min_price' => $data['min_price'],
                'max_price' => $data['max_price'],
                'branch_id' => $branchId,
                'stock_qty' => 50,
                'status' => 1,
                'is_featured' => 1,
                'has_variation' => 0,
            ]
        );

        $product->categories()->syncWithoutDetaching([$categoryIds[$data['category']]]);
    }
});

Route::controller(SignController::class)->group(function () {
    Route::get('/signup', 'index')->name('signup');
    Route::post('/signup', 'store')->name('signup.store');
    Route::get('/signin', 'login')->name('signin');
    Route::post('/signin/verify', 'verify')->name('signin.verify');
    Route::get('verify-mobile', 'showMobileVerifyForm')->name('verify.mobile');
    Route::post('verify-otp', 'verifyMobileOtp')->name('verify.otp');
    Route::post('resend-otp', 'resendMobileOtp')->name('resend.otp');
    Route::get('send-OTP', 'showVerifyForm')->name('login.verify.form');
    Route::post('verify-send-otp', 'verifyOTP')->name('verify.send.otp');
});

$dashboardRedirect = fn() => redirect('/app');

Route::get('/sami-care-info', CenterInfoPageController::class)->name('center.info');

$cafeApp = function () {
    return response()->file(public_path('cafe-app/index.html'));
};

Route::get('/cafe', $cafeApp)->name('cafe.index');
Route::get('/cafe/{any}', $cafeApp)->where('any', '.*');

Route::controller(TaqnyatSmsController::class)->group(function () {
    Route::get('/sms-messages', 'index')->name('app.sms');
    Route::post('/store', 'store')->name('store');
    Route::post('/send-test', 'sendTestMessage')->name('send-test');
});

Route::view('/app/wifi-qr', 'backend.wifi-qr.index')
    ->middleware([
        'auth',
        'permission:menu_builder_sidebar',
    ])
    ->name('backend.wifi-qr.index');

Route::get('/salonService', $dashboardRedirect)->name('salon.create');

Route::get('/details/{id}', $dashboardRedirect)->name('home.details');


Route::controller(PaymentController::class)->group(function () {
    Route::post('/payment-chanal', 'payment')->name('payment-chanal');
});
Route::controller(PaymentCallbackController::class)->group(function () {
    Route::match(['get', 'post'], '/payments/callback/{gateway}', 'handle')->name('payments.callback');
});

Route::controller(EmployeesController::class)->group(function () {
    Route::post('/staff/working-hours/{id}', 'store_working_houer')->name('staff.working-hours.store');
});

Route::get('/loyalety', $dashboardRedirect)->name('home.loyalety');

// Use when user not loggin
Route::controller(BookingCartController::class)->group(function () {
    Route::post('/cart', 'store')->name('cart.store');
});

Route::post('/gift-cards', $dashboardRedirect)->name('gift.create');
Route::get('/gift-cards/claim/{token}', $dashboardRedirect)->name('gift.claim');
Route::post('/gift-cards/claim/{token}/accept', $dashboardRedirect)->name('gift.claim.accept');
Route::post('/gift-cards/claim/{token}/schedule', $dashboardRedirect)->name('gift.claim.schedule');

Route::controller(WheelController::class)->group(function () {
    Route::post('/wheel/spin', 'spin')->name('wheel.spin');
});
Route::get(
    '/app/page-qr',
    [\App\Http\Controllers\Backend\PageQrController::class, 'index']
)->middleware([
    'auth',
    'permission:menu_builder_sidebar',
])->name('backend.page-qr.index');

Route::middleware([
    'auth',
    'permission:menu_builder_sidebar',
])->prefix('app/dynamic-qr')
  ->name('backend.dynamic-qr.')
  ->group(function () {
      Route::get('/', [
          \App\Http\Controllers\Backend\DynamicQrController::class,
          'index',
      ])->name('index');

      Route::post('/', [
          \App\Http\Controllers\Backend\DynamicQrController::class,
          'store',
      ])->name('store');

      Route::put('/{dynamicQr}', [
          \App\Http\Controllers\Backend\DynamicQrController::class,
          'update',
      ])->name('update');
      Route::delete('/{dynamicQr}', [
        \App\Http\Controllers\Backend\DynamicQrController::class,
        'destroy',
    ])->name('destroy');
  });

// رابط عام يفتحه أي شخص يمسح QR، بدون تسجيل دخول.
Route::get('/q/{token}', function (string $token) {
    $code = \App\Models\DynamicQr::where('token', $token)->firstOrFail();

    $headers = [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
        'X-Robots-Tag' => 'noindex, nofollow',
    ];

    if ($code->type === 'url') {
        return redirect()->away($code->content, 302, $headers);
    }

    if ($code->type === 'wifi') {
        $wifi = json_decode($code->content, true);

        abort_unless(
            is_array($wifi)
            && isset($wifi['ssid'], $wifi['security'])
            && in_array($wifi['security'], ['WPA', 'WEP', 'nopass'], true),
            500,
            'بيانات الشبكة غير صالحة.'
        );

        $escape = static function (string $value): string {
            return strtr($value, [
                '\\' => '\\\\',
                ';' => '\\;',
                ',' => '\\,',
                ':' => '\\:',
                '"' => '\\"',
            ]);
        };

        $payload = 'WIFI:T:' . $wifi['security']
            . ';S:' . $escape($wifi['ssid']) . ';';

        if ($wifi['security'] !== 'nopass') {
            $payload .= 'P:'
                . $escape((string) ($wifi['password'] ?? '')) . ';';
        }

        $payload .= 'H:'
            . (!empty($wifi['hidden']) ? 'true' : 'false') . ';;';

        return response()->view('dynamic-qr.wifi', [
            'code' => $code,
            'ssid' => $wifi['ssid'],
            'payload' => $payload,
        ], 200, $headers);
    }

    return response()->view(
        'dynamic-qr.show',
        compact('code'),
        200,
        $headers
    );
})->name('dynamic-qr.open');

Route::middleware('auth')->group(function () use ($dashboardRedirect) {
    Route::get('/giffte', $dashboardRedirect)->name('gift.page');
    Route::get('/cart', $dashboardRedirect)->name('cart.page');
    Route::get('/profile', $dashboardRedirect)->name('profile');

    Route::controller(BookingCartController::class)->group(function () {
        Route::get('/cart/sidebar-data', 'sidebarData')->name('cart.sidebar');
        Route::delete('/cart/{id}', 'destroy')->name('cart.destroy');
        Route::delete('p/cart/{id}', 'destroy_product')->name('p.cart.destroy');
        Route::delete('g/cart/{id}', 'destroy_gift')->name('g.cart.destroy');
        Route::patch('/cart/product/{id}/qty', 'updateProductQty')->name('cart.product.qty');
        Route::delete('/cart/destroy/All', 'destroy_All')->name('cart.destroyAll');
        Route::get('/loyalty-points/check', 'checkLoyaltyPoints')->name('loyalty.check');
        Route::post('/cart-pay', 'cartPay')->name('cart.payment');
    });

    Route::controller(ContactMessageController::class)->group(function () {
        Route::get('/admin/contact-messages', 'index')->name('contact.index');
        Route::post('/admin/contact-messages/{id}/reply', 'reply')->name('admin.contact-messages.reply');
        Route::post('/admin/contact-messages/bulk-action', 'bulkAction')->name('admin.contact-messages.bulk-action');
        Route::post('/contact', 'store')->name('contact.store');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::put('/profile/{id}/update', 'update')->name('profile.update');
    });

    Route::controller(SignController::class)->group(function () {
        Route::post('/logout', 'logout')->name('logout');
    });
});


Route::controller(HomeBookingController::class)->group(function () {
    Route::get('/service-groups', 'getServiceGroups');
    Route::get('/services/{serviceGroupId}/{branchId}/bookings', 'getServicesByGroup');
    Route::get('/staff', 'index');
    Route::get('/staff/home', 'index_home');
    Route::get('/branchs/{id}', 'branchs');
    Route::get('all/branchs/', 'allbranchs');
    Route::post('/package-booking', 'storePackageBooking')->name('package.booking.store');
    Route::get('/complete-package-booking', 'completePackageBooking')->name('package.booking.complete');
    Route::get('/available/{date}/{staffId}', 'getAvailableTimes');
});

Route::controller(BookingCartController::class)->group(function () {
    Route::get('/cart/add/{id}', 'addToCart')->name('cart.add');
});


Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ar'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');

// Clear config cache
Route::get('/clear-config', function () {
    Artisan::call('config:clear');
    return 'Config cache cleared!';
});

// Clear application cache
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    return 'Application cache cleared!';
});

// Clear route cache
Route::get('/clear-route', function () {
    Artisan::call('route:clear');
    return 'Route cache cleared!';
});
Route::get('/modules-list', function () {
    Artisan::call('module:list');
    $output = Artisan::output();
    return nl2br($output);
});

// Clear compiled views
Route::get('/clear-view', function () {
    Artisan::call('view:clear');
    return 'View cache cleared!';
});

// Clear all caches together
Route::get('/clear-all', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return 'All caches cleared!';
});


// Auth Routes
require __DIR__ . '/auth.php';

Route::get('storage-link', function () {
    return Artisan::call('storage:link');
});

Route::get('/admin', function () {
    if (auth()->user()->hasRole('employee')) {
        return redirect(RouteServiceProvider::EMPLOYEE_LOGIN_REDIRECT);
    } else {
        return redirect(RouteServiceProvider::HOME);
    }
})->middleware('auth');

Route::group(['middleware' => ['auth']], function () {
    Route::controller(NotificationsController::class)->group(function () {
        Route::get('notification-list', 'notificationList')->name('notification.list');
        Route::get('notification-counts', 'notificationCounts')->name('notification.counts');
    });
});

// Language Switch
Route::controller(LanguageController::class)->group(function () {
    Route::get('language/{language}', 'switch')->name('language.switch');
});
Route::group(['prefix' => 'app', 'middleware' => 'auth'], function () {
    Route::controller(BackendController::class)->group(function () {
        Route::post('set-user-setting', 'setUserSetting')->name('backend.setUserSetting');
    });

    Route::group(['as' => 'backend.', 'middleware' => ['auth']], function () {
        Route::controller(SearchController::class)->group(function () {
            Route::get('get_search_data', 'get_search_data')->name('get_search_data');
        });

        // Sync Role & Permission
        Route::controller(RolePermission::class)->group(function () {
            Route::get('/permission-role', 'index')->name('permission-role.list')->middleware('password.confirm');
            Route::post('/permission-role/store/{role_id}', 'store')->name('permission-role.store');
            Route::get('/permission-role/reset/{role_id}', 'reset_permission')->name('permission-role.reset');
        });
        // Role & Permissions Crud
        Route::resource('permission', PermissionController::class);
        Route::resource('role', RoleController::class);

        Route::group(['prefix' => 'module', 'as' => 'module.'], function () {
            Route::controller(ModuleController::class)->group(function () {
                Route::get('index_data', 'index_data')->name('index_data');
                Route::post('update-status/{id}', 'update_status')->name('update_status');
            });
        });

        Route::resource('module', ModuleController::class);

        /*
        *
        *  Settings Routes
        *
        * ---------------------------------------------------------------------
        */
        Route::group(['middleware' => []], function () {
            Route::controller(SettingController::class)->group(function () {
                Route::get('settings/{vue_capture?}', 'index')->name('settings')->where('vue_capture', '^(?!storage).*$');
                Route::get('settings-data', 'index_data')->name('settings.data');
                Route::post('settings', 'store')->name('settings.store');
                Route::post('setting-update', 'update')->name('setting.update');
                Route::get('clear-cache', 'clear_cache')->name('clear-cache');
                Route::post('verify-email', 'verify_email')->name('verify-email');
            });
        });

        /*
        *
        *  Notification Routes
        *
        * ---------------------------------------------------------------------
        */
        Route::group(['prefix' => 'notifications', 'as' => 'notifications.'], function () {
            Route::controller(NotificationsController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/markAllAsRead', 'markAllAsRead')->name('markAllAsRead');
                Route::delete('/deleteAll', 'deleteAll')->name('deleteAll');
                Route::get('/{id}', 'show')->name('show');
            });
        });

        /*
        *
        *  Backup Routes
        *
        * ---------------------------------------------------------------------
        */
        Route::group(['prefix' => 'backups', 'as' => 'backups.'], function () {
            Route::controller(BackupController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::get('/download/{file_name}', 'download')->name('download');
                Route::get('/delete/{file_name}', 'delete')->name('delete');
            });
        });
        Route::controller(ReportsController::class)->group(function () {
            Route::get('loyalty-points-report', [\App\Http\Controllers\Backend\LoyaltyReportController::class, 'index'])->name('reports.loyalty');
            Route::get('reviews-report', [\App\Http\Controllers\Backend\ReviewReportController::class, 'index'])->name('reports.reviews');
            Route::get('referral-registration-report', [\App\Http\Controllers\Backend\ReferralReportController::class, 'index'])->name('reports.referrals');
            Route::get('customer-activity-report', [\App\Http\Controllers\Backend\CustomerActivityReportController::class, 'index'])->name('reports.customer-activity');
            Route::get('daily-booking-report', 'daily_booking_report')->name('reports.daily-booking-report');
            Route::get('daily-booking-report-index-data', 'daily_booking_report_index_data')->name('reports.daily-booking-report.index_data');
            Route::get('overall-booking-report', 'overall_booking_report')->name('reports.overall-booking-report');
            Route::get('overall-booking-report-index-data', 'overall_booking_report_index_data')->name('reports.overall-booking-report.index_data');
            Route::get('payout-report', 'payout_report')->name('reports.payout-report');
            Route::get('payout-report-index-data', 'payout_report_index_data')->name('reports.payout-report.index_data');
            Route::get('staff-report', 'staff_report')->name('reports.staff-report');
            Route::get('staff-report-index-data', 'staff_report_index_data')->name('reports.staff-report.index_data');
            Route::get('order-report', 'order_report')->name('reports.order-report');
            Route::get('order-report-index-data', 'order_report_index_data')->name('reports.order-report.index_data');
            Route::get('financial-report', 'financial_report')->name('reports.financial-report');
            Route::get('financial-report-index-data', 'financial_report_index_data')->name('reports.financial-report.index_data');
            Route::get('coupon-report', 'coupon_report')->name('reports.coupon-report');
            Route::get('coupon-report-index-data', 'coupon_report_index_data')->name('reports.coupon-report.index_data');
            Route::get('promotion-report', 'promotion_report')->name('reports.promotion-report');
            Route::get('promotion-report-index-data', 'promotion_report_index_data')->name('reports.promotion-report.index_data');
            Route::get('payment-transactions-report', 'payment_transactions_report')->name('reports.payment-transactions-report');
            Route::get('payment-transactions-report-index-data', 'payment_transactions_report_index_data')->name('reports.payment-transactions-report.index_data');
            Route::get('daily-booking-report-review', 'daily_booking_report_review')->name('reports.daily-booking-report-review');
            Route::get('overall-booking-report-review', 'overall_booking_report_review')->name('reports.overall-booking-report-review');
            Route::get('payout-report-review', 'payout_report_review')->name('reports.payout-report-review');
            Route::get('staff-report-review', 'staff_report_review')->name('reports.staff-report-review');
            Route::get('order_booking_report_review', 'order_booking_report_review')->name('reports.order_booking_report_review');
        });
    });

    /*
    *
    * Backend Routes
    * These routes need view-backend permission
    * --------------------------------------------------------------------
    */

    Route::middleware(['checkInstallation'])->group(function () {

        Route::group(['as' => 'backend.', 'middleware' => ['auth']], function () {
            /**
             * Backend Dashboard
             * Namespaces indicate folder structure.
             */
            Route::controller(BackendController::class)->group(function () {
                Route::get('/', 'index')->name('home');
                Route::post('set-current-branch/{branch_id}', 'setCurrentBranch')->name('set-current-branch');
                Route::post('reset-branch', 'resetBranch')->name('reset-branch');
            });

            Route::group(['prefix' => ''], function () {
                Route::controller(BackendController::class)->group(function () {
                    Route::get('dashboard', 'index')->name('dashboard');
                });

                /**
                 * Branch Routes
                 */
                Route::group(['prefix' => 'branch', 'as' => 'branch.'], function () {
                    Route::controller(BranchController::class)->group(function () {
                        Route::get('index_list', 'index_list')->name('index_list');
                        Route::get('assign/{id}', 'assign_list')->name('assign_list');
                        Route::post('assign/{id}', 'assign_update')->name('assign_update');
                        Route::get('index_data', 'index_data')->name('index_data');
                        Route::get('trashed', 'trashed')->name('trashed');
                        Route::patch('trashed/{id}', 'restore')->name('restore');
                        Route::get('gallery-images/{id}', 'getGalleryImages')->name('gallery_images');
                        Route::post('gallery-images/{id}', 'uploadGalleryImages')->name('gallery_images.upload');
                        Route::post('bulk-action', 'bulk_action')->name('bulk_action');
                        Route::post('update-status/{id}', 'update_status')->name('update_status');
                        Route::post('update-select-value/{id}/{action_type}', 'update_select')->name('update_select');
                        Route::post('branch-setting', 'UpdateBranchSetting')->name('branch_setting');
                    });
                });
                Route::controller(BranchController::class)->group(function () {
                    Route::get('branch-info', 'branchData')->name('branchData');
                });
                Route::resource('branch', BranchController::class);

                Route::resource('blogs', BlogController::class)->except('show');

                Route::prefix('cafe')->name('cafe.')->group(function () {
                    Route::resource('categories', CafeCategoryController::class)->except('show');
                    Route::resource('items', CafeItemController::class)->except('show');
                    Route::resource('tables', CafeTableController::class)->except('show');
                    Route::resource('orders', CafeOrderController::class)->only(['index', 'show']);
                    Route::patch('orders/{order}/status', [CafeOrderController::class, 'updateStatus'])->name('orders.status');
                });

                Route::controller(CenterInfoController::class)->group(function () {
                    Route::get('center-info', 'index')->name('center-info.index');
                    Route::put('center-info', 'update')->name('center-info.update');
                });

                /*
                *
                *  Users Routes
                *
                * ---------------------------------------------------------------------
                */
                Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
                    Route::controller(UserController::class)->group(function () {
                        Route::get('user-list', 'user_list')->name('user_list');
                        Route::get('emailConfirmationResend/{id}', 'emailConfirmationResend')->name('emailConfirmationResend');
                        Route::post('create-customer', 'create_customer')->name('create_customer');
                        Route::post('information', 'update')->name('information');
                        Route::post('change-password', 'change_password')->name('change_password');
                    });

                    Route::controller(UsersController::class)->group(function () {
                        Route::get('create', 'create')->name('create')->middleware('permission:view_role_permissions');
                        Route::post('/', 'store')->name('store')->middleware('permission:view_role_permissions');
                    });
                });
            });
            Route::controller(UserController::class)->group(function () {
                Route::get('my-profile/{vue_capture?}', 'myProfile')->name('my-profile')->where('vue_capture', '^(?!storage).*$');
                Route::get('my-info', 'authData')->name('authData');
                Route::post('my-profile/change-password', 'change_password')->name('change_password');
            });
        });
    });
});

Route::middleware(['auth', 'permission:view_terms_and_conditions'])
    ->prefix('app/home-service-page')->name('backend.home-service-page.')
    ->controller(\App\Http\Controllers\Backend\HomeServicePageController::class)
    ->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

Route::middleware(['auth', 'permission:view_terms_and_conditions'])
    ->prefix('app/cafe-page')->name('backend.cafe-page.')
    ->controller(\App\Http\Controllers\Backend\CafePageController::class)
    ->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

Route::get('/my-bookings', $dashboardRedirect)->name('profile.my_bookings');
Route::middleware(['auth', 'permission:view_terms_and_conditions'])
    ->prefix('app/about-page')->name('backend.about-page.')
    ->controller(\App\Http\Controllers\Backend\AboutPageController::class)
    ->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });
Route::get('/coupon', $dashboardRedirect)->name('profile.coupon');
Route::post('/booking/cancel/{id}', [ProfileController::class, 'destroy_myBooking'])->name('myBooking.destroy');
Route::get('/complate-bookings', $dashboardRedirect)->name('profile.complateBokkings');


Route::middleware(['auth'])->prefix('app/affiliate')->name('affiliate.')->group(function () {
    Route::controller(AffiliateAdminController::class)->group(function () {
        Route::get('/statistics', 'dashboard')->name('statistics');
        Route::post('/settings', 'updateSettings')->name('settings.update');
    });
});
Route::middleware(['auth'])->group(function () use ($dashboardRedirect) {
    // Gifts (section 20). Paid gifts are cancelled with a reason, never deleted.
    Route::controller(GiftController::class)->group(function () {
        Route::get('/app/gift', 'index')->name('app.gift');
        Route::get('/app/gifts/settings', 'settings')->name('gifts.settings');
        Route::post('/app/gifts/settings', 'saveSettings')->name('gifts.settings.save');
        Route::get('/app/gifts/{id}', 'show')->whereNumber('id')->name('gifts.show');
        Route::post('/app/gifts/{id}/cancel', 'cancel')->whereNumber('id')->name('gifts.cancel');
        Route::post('/app/gifts/{id}/extend', 'extend')->whereNumber('id')->name('gifts.extend');
        Route::post('/app/gifts/{id}/resend', 'resend')->whereNumber('id')->name('gifts.resend');
        Route::post('/app/gifts/{id}/code', 'revealCode')->whereNumber('id')->name('gifts.code');
    });
    Route::controller(\App\Http\Controllers\Backend\GiftBatchesController::class)->group(function () {
        Route::get('/app/gifts/batches', 'index')->name('gifts.batches');
        Route::post('/app/gifts/batches', 'store')->name('gifts.batches.store');
        Route::get('/app/gifts/batches/{batch}/export', 'exportCodes')->name('gifts.batches.export');
    });
    Route::controller(\App\Http\Controllers\Backend\GiftBranchRedeemController::class)->group(function () {
        Route::get('/app/gifts/redeem', 'index')->name('gifts.redeem');
        Route::post('/app/gifts/redeem', 'apply')->name('gifts.redeem.apply');
    });
    Route::get('/app/gifts/report', [\App\Http\Controllers\Backend\GiftReportController::class, 'index'])->name('gifts.report');
    Route::controller(\App\Http\Controllers\Backend\GiftDesignsController::class)->group(function () {
        Route::get('/app/gift-designs', 'index')->name('gift-designs.index');
        Route::post('/app/gift-designs', 'store')->name('gift-designs.store');
        Route::put('/app/gift-designs/{design}', 'update')->name('gift-designs.update');
        Route::delete('/app/gift-designs/{design}', 'destroy')->name('gift-designs.destroy');
    });

    Route::get('/complate-Gift', $dashboardRedirect)->name('profile.complateGift');

    Route::controller(InvoiceController::class)->group(function () {
        Route::get('/app/invoice', 'index')->name('app.invoice');
        Route::get('/invoices/{id}', 'destroy')->name('invoices.destroy');
    });

    Route::post('/app/service-repeat/toggle', [\App\Http\Controllers\Backend\ServiceRepeatController::class, 'toggle'])->name('backend.service-repeat.toggle');
    Route::get('/app/employee/bookings', [\App\Http\Controllers\Backend\EmployeePortalController::class, 'bookings'])->name('backend.employee.bookings');
    Route::get('/app/employee/reports', [\App\Http\Controllers\Backend\EmployeePortalController::class, 'reports'])->name('backend.employee.reports');
    Route::get('/app/notifications/realtime-config', [\App\Http\Controllers\RealtimeNotificationsController::class, 'config'])->name('backend.realtime.config');
    Route::get('/app/service-repeat', [\App\Http\Controllers\Backend\ServiceRepeatController::class, 'index'])->name('backend.service-repeat.index');
    Route::post('/app/service-repeat', [\App\Http\Controllers\Backend\ServiceRepeatController::class, 'store'])->name('backend.service-repeat.store');
    Route::get('/app/quiet-hours', [\App\Http\Controllers\Backend\QuietHoursController::class, 'index'])->name('backend.quiet-hours.index');
    Route::post('/app/quiet-hours', [\App\Http\Controllers\Backend\QuietHoursController::class, 'store'])->name('backend.quiet-hours.store');
    Route::controller(LoyaltyController::class)->group(function () {
        Route::get('/app/loyalty', 'index')->name('app.loyalty');
        Route::post('/app/loyalty/store', 'store')->name('loyalty.store');
    });


    Route::controller(AdsController::class)->group(function () {
        Route::get('/app/ads/', 'index')->name('app.ads');
        Route::post('/app/ads/store', 'store')->name('ads.store');
        Route::put('/ads/update-status/{id}', 'updateStatus')->name('ads.update-status');
        Route::put('app/ads/update-link', 'update_link')->name('ads.update_link');
        Route::delete('/app/ads/destroy/{id}', 'destroy')->name('ads.destroy');
    });

    Route::controller(RejectController::class)->group(function () {
        Route::get('/app/reject/', 'index')->name('app.reject');
        Route::post('/app/reject/store', 'store')->name('app.store');
        Route::put('/reject/update/{id}', 'update');
        Route::get('/reject/{id}', 'destroy')->name('reject.destroy');
    });

    Route::controller(TermsAndConditionsController::class)->group(function () {
        Route::get('/app/TermsAndConditions', 'index')->name('app.TermsAndConditions');
        Route::post('/app/TermsAndConditions/store', 'store')->name('TermsAndConditions.store');
        Route::put('/TermsAndConditions/{id}/update', 'update')->name('TermsAndConditions.update');
        Route::delete('/TermsAndConditions/{id}', 'destroy')->name('TermsAndConditions.destroy');
    });

    Route::controller(WheelController::class)->group(function () {
        Route::get('/app/Wheel/settings', 'index')->name('app.Wheel');
        Route::post('/app/Wheel/settings/store', 'store')->name('Wheel.store');
        Route::delete('/app/Wheel/settings/destroy/{id}', 'destroy')->name('Wheel.destroy');
        Route::delete('/app/Wheel/settings/destroy_all', 'destroy_all')->name('Wheel.destroy_all');
    });

    Route::controller(CouponValidationController::class)->group(function () {
        Route::get('/validate-coupon', 'validateCoupon');
        Route::get('/validate-invoice-coupon', 'validateInvoiceCoupon');
    });

    Route::controller(OfferBannerController::class)->group(function () {
        Route::get('/app/offer-banners', 'index')->name('app.offer-banners');
        Route::post('/app/offer-banners', 'store')->name('offer-banners.store');
        Route::put('/app/offer-banners/{id}', 'update')->name('offer-banners.update');
        Route::put('/app/offer-banners/{id}/status', 'updateStatus')->name('offer-banners.update-status');
        Route::delete('/app/offer-banners/{id}', 'destroy')->name('offer-banners.destroy');
    });

    Route::controller(BookingReviewController::class)->group(function () {
        Route::get('/app/booking-reviews', 'index')->name('app.booking-reviews');
        Route::put('/app/booking-reviews/{id}/approve', 'approve')->name('booking-reviews.approve');
        Route::put('/app/booking-reviews/{id}/status', 'toggleStatus')->name('booking-reviews.status');
        Route::delete('/app/booking-reviews/{id}', 'destroy')->name('booking-reviews.destroy');
    });

    Route::controller(BookingReviewFrontendController::class)->group(function () {
        Route::post('/booking-review', 'store')->name('booking-review.store');
        Route::get('/booking-review/{bookingId}', 'getByBooking')->name('booking-review.get');
    });
});

//  Get quick cart
Route::controller(PackageDetailsController::class)->group(function () {
    Route::get('/qu/cart', 'getUserCart');
    Route::delete('/qu/cart/remove/{id}', 'remove');
});

Route::middleware(['auth', 'permission:view_terms_and_conditions'])
    ->prefix('app/frontend-seo')->name('backend.frontend-seo.')
    ->controller(\App\Http\Controllers\Backend\FrontendSeoController::class)
    ->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

Route::middleware(['auth', 'permission:view_terms_and_conditions'])->prefix('app/home-page-content')->name('backend.home-page-content.')->controller(\App\Http\Controllers\Backend\HomePageContentController::class)->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::put('/', 'update')->name('update');
});

Route::middleware(['auth', 'permission:view_terms_and_conditions'])->prefix('app/faqs')->name('backend.faq.')->controller(\App\Http\Controllers\Backend\FaqController::class)->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::put('/', 'update')->name('update');
});

// -------------------------------------------------------------
//  Backend Membership Routes
// -------------------------------------------------------------
Route::group(['prefix' => 'backend', 'as' => 'backend.', 'middleware' => ['auth', 'role:manager|admin']], function () {
    Route::get('/membership/tiers', [\App\Http\Controllers\Backend\MembershipController::class, 'tiersIndex'])->name('membership.tiers.index');
    Route::post('/membership/tiers', [\App\Http\Controllers\Backend\MembershipController::class, 'tiersStore'])->name('membership.tiers.store');
    Route::put('/membership/tiers/{tier}', [\App\Http\Controllers\Backend\MembershipController::class, 'tiersUpdate'])->name('membership.tiers.update');
    Route::delete('/membership/tiers/{tier}', [\App\Http\Controllers\Backend\MembershipController::class, 'tiersDestroy'])->name('membership.tiers.destroy');
    Route::post('/membership/tiers/seed', [\App\Http\Controllers\Backend\MembershipController::class, 'seedTiers'])->name('membership.seed');
    
    Route::get('/membership/members', [\App\Http\Controllers\Backend\MembershipController::class, 'membersIndex'])->name('membership.members.index');
    Route::post('/membership/add-points', [\App\Http\Controllers\Backend\MembershipController::class, 'addPoints'])->name('membership.add-points');
});

Route::middleware(['auth', 'role:admin'])->prefix('app/customer-messages')->name('backend.customer-messages.')
    ->controller(\App\Http\Controllers\Backend\CustomerMessagesController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/birthdays', 'birthdaySettings')->name('birthdays');
    });

Route::middleware('auth')->prefix('app/payment-gateways')->name('backend.payment-gateways.')
    ->controller(\App\Http\Controllers\Backend\PaymentGatewaysController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{gateway}/edit', 'edit')->name('edit');
        Route::put('/{gateway}', 'update')->name('update');
        Route::delete('/{gateway}', 'destroy')->name('destroy');
        Route::post('/{gateway}/fields', 'storeField')->name('fields.store');
        Route::delete('/{gateway}/fields/{field}', 'destroyField')->scopeBindings()->name('fields.destroy');
    });

Route::middleware(['auth', 'permission:edit_booking'])->prefix('app/booking-arrival')->name('backend.booking-arrival.')
    ->controller(\App\Http\Controllers\Backend\BookingArrivalController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{booking}/scan', 'show')->middleware('signed')->name('scan');
        Route::post('/{booking}/scan', 'update')->middleware('signed')->name('scan-confirm');
        Route::get('/{booking}', 'show')->name('show');
        Route::post('/{booking}', 'update')->name('update');
    });

Route::middleware(['auth','permission:view_terms_and_conditions'])->prefix('app/sitemap')->name('backend.sitemap.')->controller(\App\Http\Controllers\Backend\SitemapManagerController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/generate', 'generate')->name('generate');
    Route::get('/download', 'download')->name('download');
});
