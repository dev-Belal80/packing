<?php

namespace App\Support;

use App\Models\User;

final class ApiAccess
{
    /**
     * Internal role names are preserved in storage, but the API exposes this normalized contract.
     */
    private const ROLE_ALIASES = [
        'super_admin' => 'super_admin',
        'manager' => 'manager',
        'station_admin' => 'manager',
        'receptionist' => 'receptionist',
        'reception' => 'receptionist',
        'production_supervisor' => 'production_supervisor',
        'production' => 'production_supervisor',
        'export_officer' => 'export_officer',
        'export' => 'export_officer',
        'stock' => 'manager',
    ];

    private const ALL_SCREENS = [
        'dashboard',
        'raw-receipt',
        'receipts-list',
        'gate-inquiries-list',
        'production-order',
        'sort-record',
        'pallet',
        'shipping-policy',
        'reports',
        'settings',
        'permissions',
        // Reception workflow screens
        'delivery-order',      // أمر توريد خام
        'gate-inquiry',        // استعلام بوابة
        'scale-note',          // علم وزن
        'transport-cost',      // توزيع تكلفة النقل
        'delivery-orders-list', // قائمة أوامر التوريد
        // Production workflow screens
        'production-order',    // أمر إنتاج
        'sort-record',        // سجل فرز
        'attendance',         // حضور العمال
        // Export workflow screens
        'pallet',             // منصةتصدير
        'shipping-policy',    // بوليصة شحن
        'inventory',          // المخزون

        ];

    private const ALL_ACTIONS = [
        'receipt.approve',
        'receipt.reject',
        'receipt.price',
        'order.dispatch',
        'order.pause',
        'order.cancel',
        'pallet.cooling',
        'pallet.confirm_receipt',
        'shipping.approve',
        'reports.view',
        'inventory.view',
        'stock.pricing',
        'shipment.dispatch',
        'user.manage',
        'tenant.provision',
    ];

    public static function normalizedRole(?User $user): string
    {
        if (! $user) {
            return 'receptionist';
        }

        $candidates = array_filter([
            $user->role,
            $user->getRoleNames()->first(),
        ]);

        foreach ($candidates as $candidate) {
            $normalized = self::ROLE_ALIASES[$candidate] ?? $candidate;

            if (in_array($normalized, ['super_admin', 'manager', 'receptionist', 'production_supervisor', 'export_officer'], true)) {
                return $normalized;
            }
        }

        return 'receptionist';
    }

    public static function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => self::normalizedRole($user),
        ];
    }

    public static function profileForUser(User $user): array
    {
        $role = self::normalizedRole($user);

        return [
            'user' => self::userPayload($user),
            'permissions' => self::permissionsForRole($role),
        ];
    }

    public static function permissionsForRole(string $role): array
    {
        $profiles = [
            'super_admin' => [
                'screens' => self::ALL_SCREENS,
                'actions' => self::ALL_ACTIONS,
            ],
            'manager' => [
                'screens' => self::ALL_SCREENS,
                'actions' => self::ALL_ACTIONS,
            ],
            'receptionist' => [
                'screens' => [
                    'dashboard',
                    'raw-receipt',
                    'receipts-list',
                    'gate-inquiries-list',
                    'delivery-order',
                    'delivery-orders-list',
                    'gate-inquiry',
                    'scale-note',
                ],
                'actions' => [
                    'receipt.price',

                ],
            ],
            'production_supervisor' => [
                'screens' => [
                    'dashboard',
                    'production-order',
                    'sort-record',
                ],
                'actions' => [
                    'order.dispatch',
                    'order.pause',
                    'order.cancel',
                ],
            ],
            'export_officer' => [
                'screens' => [
                    'dashboard',
                    'pallet',
                    'shipping-policy',
                ],
                'actions' => [
                    'pallet.cooling',
                    'pallet.confirm_receipt',
                    'shipping.approve',
                ],
            ],
        ];

        return $profiles[$role] ?? [
            'screens' => [],
            'actions' => [],
        ];
    }

    public static function routePermissionForAction(?string $action): ?string
    {
        $permissions = self::routePermissions();

        if ($action === null) {
            return 'route.unmapped';
        }

        return array_key_exists($action, $permissions) ? $permissions[$action] : 'route.unmapped';
    }

    public static function canAccess(User $user, string $requiredPermission): bool
    {
        $role = self::normalizedRole($user);

        if ($role === 'super_admin') {
            return true;
        }

        $profile = self::permissionsForRole($role);

        return in_array($requiredPermission, $profile['screens'], true)
            || in_array($requiredPermission, $profile['actions'], true);
    }

    public static function routePermissions(): array
    {
        return [
            'App\\Http\\Controllers\\Api\\Auth\\AuthController@me' => null,
            'App\\Http\\Controllers\\Api\\Auth\\AuthController@logout' => null,
            'App\\Http\\Controllers\\Api\\Auth\\AuthController@tokenLogout' => null,

            'App\\Http\\Controllers\\Api\\Reception\\GateInquiryController@index' => 'gate-inquiries-list',
            'App\\Http\\Controllers\\Api\\Reception\\GateInquiryController@store' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\GateInquiryController@show' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\GateInquiryController@update' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\GateInquiryController@destroy' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\ScaleNoteController@index' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\ScaleNoteController@store' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\ScaleNoteController@show' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\ScaleNoteController@update' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\ScaleNoteController@destroy' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\RawReceiptController@index' => 'receipts-list',
            'App\\Http\\Controllers\\Api\\Reception\\RawReceiptController@store' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\RawReceiptController@show' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\RawReceiptController@update' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\RawReceiptController@destroy' => 'raw-receipt',
            'App\\Http\\Controllers\\Api\\Reception\\RawReceiptController@approve' => 'receipt.approve',
            'App\\Http\\Controllers\\Api\\Reception\\RawDeliveryOrderController@index' => 'delivery-order',
            'App\\Http\\Controllers\\Api\\Reception\\RawDeliveryOrderController@store' => 'delivery-order',
            'App\\Http\\Controllers\\Api\\Reception\\RawDeliveryOrderController@show' => 'delivery-order',
            'App\\Http\\Controllers\\Api\\Reception\\RawDeliveryOrderController@update' => 'delivery-order',
            'App\\Http\\Controllers\\Api\\Reception\\RawDeliveryOrderController@destroy' => 'delivery-order',
            'App\\Http\\Controllers\\Api\\Reception\\RawDeliveryOrderController@confirm' => 'delivery-order',

            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@index' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@store' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@show' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@update' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@destroy' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@dispatch' => 'order.dispatch',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@pause' => 'order.pause',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@cancel' => 'order.cancel',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@index' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@store' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@show' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@update' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@destroy' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@index' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@store' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@show' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@update' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@destroy' => 'production-order',

            'App\\Http\\Controllers\\Api\\Export\\PalletController@index' => 'pallet',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@store' => 'pallet',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@show' => 'pallet',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@update' => 'pallet',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@destroy' => 'pallet',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@cooling' => 'pallet.cooling',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@confirmReceipt' => 'pallet.confirm_receipt',
            'App\\Http\\Controllers\\Api\\Export\\ShippingPolicyController@index' => 'shipping-policy',
            'App\\Http\\Controllers\\Api\\Export\\ShippingPolicyController@store' => 'shipping-policy',
            'App\\Http\\Controllers\\Api\\Export\\ShippingPolicyController@show' => 'shipping-policy',
            'App\\Http\\Controllers\\Api\\Export\\ShippingPolicyController@update' => 'shipping-policy',
            'App\\Http\\Controllers\\Api\\Export\\ShippingPolicyController@destroy' => 'shipping-policy',
            'App\\Http\\Controllers\\Api\\Export\\ShippingPolicyController@approve' => 'shipping.approve',

            'App\\Http\\Controllers\\Api\\Stock\\StockController@index' => 'inventory.view',
            'App\\Http\\Controllers\\Api\\Stock\\StockController@pricing' => 'stock.pricing',
            'App\\Http\\Controllers\\Api\\Stock\\StockController@dispatchShipment' => 'shipment.dispatch',

            'App\\Http\\Controllers\\Api\\Reports\\ReportController@dashboard' => 'reports.view',
            'App\\Http\\Controllers\\Api\\Reports\\ReportController@receiptTracking' => 'reports.view',
            'App\\Http\\Controllers\\Api\\Reports\\ReportController@productionStats' => 'reports.view',
            'App\\Http\\Controllers\\Api\\Reports\\ReportController@palletTracking' => 'reports.view',
            'App\\Http\\Controllers\\Api\\Reports\\ReportController@shipments' => 'reports.view',

            'App\\Http\\Controllers\\Api\\SuperAdmin\\TenantProvisionController@store' => 'tenant.provision',
            'App\\Http\\Controllers\\Api\\Station\\UserManagementController@store' => 'user.manage',
        ];
    }

    public static function internalRoleForApiRole(string $role): string
    {
        return match ($role) {
            'super_admin' => 'super_admin',
            'manager' => 'station_admin',
            'receptionist' => 'reception',
            'production_supervisor' => 'production',
            'export_officer' => 'export',
            default => $role,
        };
    }
}
