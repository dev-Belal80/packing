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
use App\Http\Controllers\Api\Settings\BranchController;
use App\Http\Controllers\Api\Settings\ContactController;
use App\Http\Controllers\Api\Settings\FridgeController;
use App\Http\Controllers\Api\Settings\PackhouseController;
use App\Http\Controllers\Api\Settings\PayloadTemplateController;
use App\Http\Controllers\Api\Settings\PalletTypeController;
use App\Http\Controllers\Api\Settings\PermissionsController;
use App\Http\Controllers\Api\Settings\ProductController;
use App\Http\Controllers\Api\Settings\RawMaterialTypeController;
use App\Http\Controllers\Api\Settings\ProductionLineController;
use App\Http\Controllers\Api\Settings\ProductionStageController;
use App\Http\Controllers\Api\Settings\ProductionSupervisorController;
use App\Http\Controllers\Api\Reports\ReportController;
use App\Http\Controllers\Api\Reports\StockReportController;
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
        Route::post('delivery-orders/{rawDeliveryOrder}/respond', [RawDeliveryOrderController::class, 'respond']);
        Route::apiResource('gate-inquiries', GateInquiryController::class);
        Route::apiResource('scale-notes', ScaleNoteController::class);
        Route::apiResource('raw-receipts', RawReceiptController::class);
        Route::patch('raw-receipts/{id}/approve', [RawReceiptController::class, 'approve']);
        Route::post('transport-cost/receipts', [TransportCostDistributionController::class, 'getReceipts']);
        Route::post('transport-cost/distribute', [TransportCostDistributionController::class, 'distribute']);
    });

    // Lookups for forms
    Route::prefix('settings')->group(function () {
        Route::get('branches', [BranchController::class, 'index']);
        Route::get('payloads', [PayloadTemplateController::class, 'index']);
        Route::get('payloads/{resource}', [PayloadTemplateController::class, 'show']);
        Route::get('raw-material-types', [RawMaterialTypeController::class, 'index']);
        Route::post('raw-material-types', [RawMaterialTypeController::class, 'store']);
        Route::put('raw-material-types/{rawMaterialType}', [RawMaterialTypeController::class, 'update']);
        Route::delete('raw-material-types/{rawMaterialType}', [RawMaterialTypeController::class, 'destroy']);
        Route::get('permissions', [PermissionsController::class, 'index']);
        Route::put('permissions', [PermissionsController::class, 'update']);
        Route::post('permissions/reset', [PermissionsController::class, 'reset']);
        Route::get('contacts', [ContactController::class, 'index']);
        Route::post('contacts', [ContactController::class, 'store']);
        Route::put('contacts/{contact}', [ContactController::class, 'update']);
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy']);
        Route::get('fridges', [FridgeController::class, 'index']);
        Route::post('fridges', [FridgeController::class, 'store']);
        Route::put('fridges/{fridge}', [FridgeController::class, 'update']);
        Route::delete('fridges/{fridge}', [FridgeController::class, 'destroy']);
        Route::get('packhouses', [PackhouseController::class, 'index']);
        Route::post('packhouses', [PackhouseController::class, 'store']);
        Route::put('packhouses/{packhouse}', [PackhouseController::class, 'update']);
        Route::delete('packhouses/{packhouse}', [PackhouseController::class, 'destroy']);
        Route::get('pallet-types', [PalletTypeController::class, 'index']);
        Route::post('pallet-types', [PalletTypeController::class, 'store']);
        Route::put('pallet-types/{palletType}', [PalletTypeController::class, 'update']);
        Route::delete('pallet-types/{palletType}', [PalletTypeController::class, 'destroy']);
        Route::get('products', [ProductController::class, 'index']);
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
        Route::get('production-lines', [ProductionLineController::class, 'index']);
        Route::post('production-lines', [ProductionLineController::class, 'store']);
        Route::put('production-lines/{productionLine}', [ProductionLineController::class, 'update']);
        Route::delete('production-lines/{productionLine}', [ProductionLineController::class, 'destroy']);
        Route::get('production-stages', [ProductionStageController::class, 'index']);
        Route::get('production-supervisors', [ProductionSupervisorController::class, 'index']);
    });

    Route::get('contacts', [ContactController::class, 'index']);

    // Production
    Route::prefix('production')->group(function () {
        Route::get('raw-receipts/available', [ProductionOrderController::class, 'availableRawReceipts']);
        Route::apiResource('orders', ProductionOrderController::class);
        Route::post('orders/{id}/dispatch', [ProductionOrderController::class, 'dispatch']);
        Route::post('orders/{id}/pause', [ProductionOrderController::class, 'pause']);
        Route::post('orders/{id}/cancel', [ProductionOrderController::class, 'cancel']);
        Route::apiResource('sort-records', SortRecordController::class);
        Route::post('sort-records/{sortRecord}/post', [SortRecordController::class, 'post']);
        Route::apiResource('attendance', AttendanceController::class);
    });

    // Export
    Route::prefix('export')->group(function () {
        Route::get('pallets-available-orders', [PalletController::class, 'availableOrders']);
        Route::get('pallets/available-orders', [PalletController::class, 'availableOrders']);
        Route::apiResource('pallets', PalletController::class);
        Route::post('pallets/{pallet}/cooling', [PalletController::class, 'cooling']);
        Route::post('pallets/{pallet}/confirm-receipt', [PalletController::class, 'confirmReceipt']);
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
        Route::get('stock-balance/{id}', [StockReportController::class, 'stockBalance']);

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
        Route::get('users', [\App\Http\Controllers\Api\Station\UserManagementController::class, 'index']);
        Route::post('users', [\App\Http\Controllers\Api\Station\UserManagementController::class, 'store']);
        Route::put('users/{user}', [\App\Http\Controllers\Api\Station\UserManagementController::class, 'update']);
        Route::delete('users/{user}', [\App\Http\Controllers\Api\Station\UserManagementController::class, 'destroy']);
    });
});

