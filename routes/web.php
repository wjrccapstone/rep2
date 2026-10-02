<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForecastingController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\PartOutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
// use App\Http\Controllers\BulletinController;
use App\Http\Controllers\BulletinController;

Route::get('/', fn () => redirect()->route(auth()->user()?->homeRoute() ?? 'login'));

// Serves files from the "public" disk directly through PHP instead of the
// public/storage symlink, which some hosts refuse to follow or block by path.
// The path is a query param (not a URL segment) because this host's static
// file server intercepts and 404s any URL path containing a dot/extension
// before it ever reaches Laravel's router.
Route::get('/media', function (Illuminate\Http\Request $request) {
    $path = $request->query('path', '');

    abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->name('media.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showEmailForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('password.email');
    Route::get('/forgot-password/otp', [ForgotPasswordController::class, 'showOtpForm'])->name('password.otp');
    Route::post('/forgot-password/otp', [ForgotPasswordController::class, 'verifyOtp'])->name('password.otp.verify');
    Route::post('/forgot-password/otp/resend', [ForgotPasswordController::class, 'resendOtp'])->name('password.otp.resend');
    Route::get('/forgot-password/reset', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['auth', 'no-back-cache'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Every role can manage its own account.
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.change');

    // Job Orders — admins and technicians only (not cashiers). Technicians land here.
    Route::middleware('role:admin,technician')->group(function () {
        Route::get('/job-orders', [JobOrderController::class, 'index'])->name('job-orders.index');
        Route::get('/job-orders/{job_order}/service-report', [JobOrderController::class, 'serviceReport'])->name('job-orders.service-report');
        Route::patch('/job-orders/{job_order}/progress', [JobOrderController::class, 'updateProgress'])->name('job-orders.progress.update');

        // Full create / edit / delete — admins only.
        Route::middleware('role:admin')->group(function () {
            // Registered before the resource route below: "archive-completed" would
            // otherwise be swallowed by the resource's PATCH /job-orders/{job_order}.
            Route::get('/job-orders/export', [JobOrderController::class, 'exportCsv'])->name('job-orders.export');
            Route::patch('/job-orders/archive-completed', [JobOrderController::class, 'archiveCompleted'])->name('job-orders.archive-completed');
            Route::patch('/job-orders/{job_order}/restore', [JobOrderController::class, 'restore'])->name('job-orders.restore');
            Route::resource('job-orders', JobOrderController::class)->except(['index', 'show']);
        });
    });

    Route::middleware('role:admin,cashier,technician')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/reset', [SettingsController::class, 'reset'])->name('settings.reset');
        Route::get('/settings/export', [SettingsController::class, 'export'])->name('settings.export');
    });

    // Product Inventory — admins and cashiers see every tab except Parts Out;
    // technicians are restricted (in the controller/view) to Parts Out and
    // Archive, and submit Parts Out as a request rather than recording it
    // directly. Parts Out itself is an admin/technician tool only — cashiers
    // don't request, approve, or bill parts.
    Route::middleware('role:admin,cashier,technician')->group(function () {
        Route::get('/inventory', [ProductController::class, 'index'])->name('products.index');
    });

    Route::middleware('role:admin,technician')->group(function () {
        Route::post('/inventory/parts-out', [PartOutController::class, 'store'])->name('parts-out.store');
        Route::get('/inventory/parts-out/export', [PartOutController::class, 'exportCsv'])->name('parts-out.export');
    });

    Route::middleware('role:admin,cashier')->group(function () {
        Route::post('/cashier/sale', [CashierController::class, 'sale'])->name('cashier.sale.store');
        // No file extension in the path — this host's static file server intercepts
        // and 404s any URL path containing a dot before Laravel's router sees it
        // (same issue the /media route works around). The .csv filename still comes
        // through via the Content-Disposition header in the response.
        Route::get('/cashier/forecast-dataset', [CashierController::class, 'exportDataset'])->name('cashier.dataset.export');

        Route::post('/inventory', [ProductController::class, 'store'])->name('products.store');
        Route::put('/inventory/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/inventory/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
        Route::patch('/inventory/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
        Route::delete('/inventory/archive/all', [ProductController::class, 'destroyAllArchived'])->name('products.archive.destroyAll');
        Route::delete('/inventory/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/inventory/export', [ProductController::class, 'exportCsv'])->name('products.export');
        Route::post('/inventory/import', [ProductController::class, 'importCsv'])->name('products.import');
        Route::get('/inventory/sales/export', [ProductController::class, 'exportSalesCsv'])->name('sales.export');
        Route::post('/inventory/sales/import', [ProductController::class, 'importSalesCsv'])->name('sales.import');

        // Admin-only — cashiers and technicians have no access to these.
        Route::middleware('role:admin')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/forecasting', [ForecastingController::class, 'index'])->name('forecasting.index');
            Route::get('/users/activity-log', [UserManagementController::class, 'activityLog'])->name('users.activity-log');
            Route::get('/users/activity-log/export', [UserManagementController::class, 'exportActivityLog'])->name('users.activity-log.export');
            Route::get('/users/password-reset-log', [UserManagementController::class, 'passwordResetLog'])->name('users.password-reset-log');

            Route::resource('users', UserManagementController::class)->except('show');
            Route::put('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
            Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');

            // Accept/reject technician Parts Out requests, and bill approved ones.
            Route::patch('/inventory/parts-out/{partOut}/approve', [PartOutController::class, 'approve'])->name('parts-out.approve');
            Route::patch('/inventory/parts-out/{partOut}/reject', [PartOutController::class, 'reject'])->name('parts-out.reject');
            Route::patch('/inventory/parts-out/{partOut}/bill', [PartOutController::class, 'markBilled'])->name('parts-out.bill');
        });
    });

    Route::middleware('role:admin,cashier,technician')->prefix('bulletin')->name('bulletin.')->group(function () {
        Route::get('/feed', [BulletinController::class, 'feed'])->name('feed');
        Route::get('/status', [BulletinController::class, 'status'])->name('status');
        Route::post('/{notice}/acknowledge', [BulletinController::class, 'acknowledge'])->name('acknowledge');
    });

    Route::middleware('role:admin')->prefix('bulletin')->name('bulletin.')->group(function () {
        Route::post('/', [BulletinController::class, 'store'])->name('store');
        Route::post('/{notice}/archive', [BulletinController::class, 'archive'])->name('archive');
        Route::post('/{notice}/pin', [BulletinController::class, 'pin'])->name('pin');
    });
});