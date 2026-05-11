<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Export\PalletController;
use App\Http\Controllers\Api\Export\ShippingPolicyController;
use App\Http\Controllers\Api\Production\AttendanceController;
use App\Http\Controllers\Api\Production\ProductionOrderController;
use App\Http\Controllers\Api\Production\SortRecordController;
use App\Http\Controllers\Api\Reception\RawDeliveryOrderController;
use App\Http\Controllers\Api\Reception\GateInquiryController;
use App\Http\Controllers\Api\Reception\RawReceiptController;
use App\Http\Controllers\Api\Reception\ScaleNoteController;
use App\Http\Controllers\Api\Reception\TransportCostDistributionController;
use App\Http\Controllers\Api\Settings\ContactController;
use App\Http\Controllers\Api\Settings\PackhouseController;
use App\Http\Controllers\Api\Reports\ReportController;
use App\Http\Controllers\Api\Reports\ReceptionReportsController;
use App\Http\Controllers\Api\Stock\StockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are intended for the React frontend.
| Tenancy middleware is attached on authenticated routes.
|
*/

Route::get('/health', fn () => ['ok' => true]);

Route::prefix('auth')->group(function () {
    // Public
    Route::post('login', [AuthController::class, 'login']);

    // Optional: token-based flow (kept for compatibility)
    Route::post('token', [AuthController::class, 'tokenLogin'])
        ->withoutMiddleware([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
});

// Protected
Route::middleware([\Illuminate\Auth\Middleware\Authenticate::using('sanctum'), 'tenant', 'subscription', 'api.permission'])
    ->withoutMiddleware([
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
    ])->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/token/logout', [AuthController::class, 'tokenLogout'])
        ->withoutMiddleware([
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);

    // Reception
    Route::prefix('reception')->group(function () {
        Route::apiResource('delivery-orders', RawDeliveryOrderController::class)
            ->parameters(['delivery-orders' => 'rawDeliveryOrder']);
        Route::post('delivery-orders/{rawDeliveryOrder}/confirm', [RawDeliveryOrderController::class, 'confirm']);
        Route::apiResource('gate-inquiries', GateInquiryController::class);
        Route::apiResource('scale-notes', ScaleNoteController::class);
        Route::apiResource('raw-receipts', RawReceiptController::class);
        Route::patch('raw-receipts/{id}/approve', [RawReceiptController::class, 'approve']);
        Route::post('transport-cost/receipts', [TransportCostDistributionController::class, 'getReceipts']);
        Route::post('transport-cost/distribute', [TransportCostDistributionController::class, 'distribute']);
    });

    // Lookups for forms
    Route::prefix('settings')->group(function () {
        Route::get('contacts', [ContactController::class, 'index']);
        Route::get('packhouses', [PackhouseController::class, 'index']);
    });

    // Production
    Route::prefix('production')->group(function () {
        Route::apiResource('orders', ProductionOrderController::class);
        Route::post('orders/{id}/dispatch', [ProductionOrderController::class, 'dispatch']);
        Route::post('orders/{id}/pause', [ProductionOrderController::class, 'pause']);
        Route::post('orders/{id}/cancel', [ProductionOrderController::class, 'cancel']);
        Route::apiResource('sort-records', SortRecordController::class);
        Route::apiResource('attendance', AttendanceController::class);
    });

    // Export
    Route::prefix('export')->group(function () {
        Route::apiResource('pallets', PalletController::class);
        Route::post('pallets/{id}/cooling', [PalletController::class, 'cooling']);
        Route::post('pallets/{id}/confirm-receipt', [PalletController::class, 'confirmReceipt']);
        Route::apiResource('shipping-policies', ShippingPolicyController::class);
        Route::patch('shipping-policies/{id}/approve', [ShippingPolicyController::class, 'approve']);
    });

    // Stock
    Route::prefix('stock')->group(function () {
        Route::get('inventory', [StockController::class, 'index']);
        Route::post('pricing', [StockController::class, 'pricing']);
        Route::post('dispatch-shipment', [StockController::class, 'dispatchShipment']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('dashboard', [ReportController::class, 'dashboard']);
        Route::get('receipt-tracking/{id}', [ReportController::class, 'receiptTracking']);
        Route::get('production-stats', [ReportController::class, 'productionStats']);
        Route::get('pallet-tracking/{id}', [ReportController::class, 'palletTracking']);
        Route::get('shipments', [ReportController::class, 'shipments']);

        // Reception Reports (PDF + Excel)
        Route::prefix('reception')->group(function () {
            // Raw Receipts
            Route::get('raw-receipts/pdf', [ReceptionReportsController::class, 'rawReceiptsPdf']);
            Route::get('raw-receipts/excel', [ReceptionReportsController::class, 'rawReceiptsExcel']);

            // Gate Inquiries
            Route::get('gate-inquiries/pdf', [ReceptionReportsController::class, 'gateInquiriesPdf']);
            Route::get('gate-inquiries/excel', [ReceptionReportsController::class, 'gateInquiriesExcel']);

            // Scale Notes
            Route::get('scale-notes/pdf', [ReceptionReportsController::class, 'scaleNotesPdf']);
            Route::get('scale-notes/excel', [ReceptionReportsController::class, 'scaleNotesExcel']);

            // Delivery Orders
            Route::get('delivery-orders/pdf', [ReceptionReportsController::class, 'deliveryOrdersPdf']);
            Route::get('delivery-orders/excel', [ReceptionReportsController::class, 'deliveryOrdersExcel']);

            // Daily full report (all sheets in one file)
            Route::get('daily/excel', [ReceptionReportsController::class, 'dailyFullExcel']);
        });
    });

    // Existing admin/provisioning endpoints (kept)
    Route::prefix('super')->middleware(['role:super_admin'])->group(function () {
        Route::post('tenants', [\App\Http\Controllers\Api\SuperAdmin\TenantProvisionController::class, 'store']);
    });

    Route::prefix('station')->middleware(['role:station_admin'])->group(function () {
        Route::post('users', [\App\Http\Controllers\Api\Station\UserManagementController::class, 'store']);
    });
});

