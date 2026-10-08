<?php

use App\Http\Controllers\AdminMonitoringController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandRequestController;
use App\Http\Controllers\ManagerOperationsController;
use App\Http\Controllers\PublicVerificationController;
use App\Http\Controllers\ScorecardController;
use App\Livewire\DistributorForecastSession;
use App\Livewire\Manager\CreateUserModal;
use App\Livewire\ManagerForecastOverview;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/verify/{code}', [PublicVerificationController::class, 'show'])
    ->whereUuid('code')
    ->name('verify.record');

Route::middleware(['auth', 'role:bank'])->group(function (): void {
    Route::view('/bank/verification', 'auth.bank-verification')->name('bank.verification');
    Route::get('/bank/verification/lookup', [PublicVerificationController::class, 'lookup'])->name('bank.verification.lookup');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'role:distributor'])->group(function (): void {
    Route::get('/demand/request', DistributorForecastSession::class)->name('demand.request');
    Route::post('/demand/request', [DemandRequestController::class, 'store'])->name('demand.request.store');

    Route::get('/receipt/confirm', [DemandRequestController::class, 'receiptForm'])->name('receipt.confirm');
    Route::post('/receipt/confirm', [DemandRequestController::class, 'confirmReceipt'])->name('receipt.confirm.store');

    Route::get('/sales/entry', [DemandRequestController::class, 'salesForm'])->name('sales.entry');
    Route::post('/sales/entry', [DemandRequestController::class, 'recordSales'])->name('sales.entry.store');

    Route::get('/scorecard', [ScorecardController::class, 'show'])->name('scorecard');

    Route::redirect('/distributor/dashboard', '/dashboard');
    Route::redirect('/distributor/weekly-request', '/demand/request');
    Route::redirect('/distributor/confirm-receipt', '/receipt/confirm');
    Route::redirect('/distributor/sales-entry', '/sales/entry');
    Route::redirect('/analytics/distributor-scorecard', '/scorecard');
});

Route::middleware(['auth', 'role:manager,admin'])->prefix('manager')->name('manager.')->group(function (): void {
    Route::get('/users/create', CreateUserModal::class)->name('users.create');
    Route::get('/forecasts', ManagerForecastOverview::class)->name('forecasts');
    Route::get('/review-queue', [ManagerOperationsController::class, 'reviewQueue'])->name('review-queue');
    Route::post('/review-queue/review', [ManagerOperationsController::class, 'review'])->name('review-queue.review');
    Route::get('/complaints', [ManagerOperationsController::class, 'complaints'])->name('complaints');
    Route::patch('/complaints/{complaint}/resolve', [ManagerOperationsController::class, 'resolveComplaint'])->name('complaints.resolve');
});

Route::middleware(['auth', 'role:logistics,manager,admin'])->prefix('manager')->name('manager.')->group(function (): void {
    Route::get('/dispatch', [ManagerOperationsController::class, 'dispatchQueue'])->name('dispatch');
    Route::post('/dispatch/{demandRequest}', [ManagerOperationsController::class, 'dispatch'])->name('dispatch.store');
    Route::redirect('/dispatch-management', '/manager/dispatch');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/ai-monitoring', [AdminMonitoringController::class, 'show'])->name('ai-monitoring');
    Route::post('/ai-monitoring/retrain', [AdminMonitoringController::class, 'triggerRetraining'])->name('ai-monitoring.retrain');
});
