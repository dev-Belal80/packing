<?php

namespace App\Support;

use App\Models\RoleScreenPermission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

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
        'scale-notes-list',
        'production-order',
        'sort-record',
        'pallet',
        'shipping-policy',
        'reports',
        'reception-reports',
        'settings',
        'permissions',
        'pallet-builder',
        'delivery-order',      // أمر توريد خام
        'gate-inquiry',        // استعلام بوابة
        'scale-note',          // علم وزن
        'transport-cost',      // توزيع تكلفة النقل
        'delivery-orders-list', // قائمة أوامر التوريد
        'production-order',    // أمر إنتاج
        'production-orders-list',
        'production-order-view',
        'attendance',         // حضور العمال
        'inventory',          // المخزون
    ];

    private const ALL_ACTIONS = [
        'receipt.approve',
        'receipt.reject',
        'receipt.price',
        'order.dispatch',
        'order.pause',
        'order.cancel',
        'sort-record.post',
        'pallet.create',
        'pallet.update',
        'pallet.delete',
        'pallet.cooling',
        'pallet.confirm',
        'shipping.approve',
        'reports.view',
        'inventory.view',
        'stock.pricing',
        'shipment.dispatch',
        'user.manage',
        'tenant.provision',
    ];

    /**
     * @return array<int, string>
     */
    public static function allActionIds(): array
    {
        return self::ALL_ACTIONS;
    }

    /**
     * Canonical list of screen permission keys.
     *
     * This is used by the permissions UI so the frontend doesn't need a hard-coded list.
     *
     * @return array<int, string>
     */
    public static function allScreenIds(): array
    {
        $screens = collect(self::ALL_SCREENS);

        $fromRoutes = collect(self::routePermissions())
            ->values()
            ->filter(fn ($permission) => is_string($permission) && $permission !== 'route.unmapped')
            ->reject(fn (string $permission) => in_array($permission, self::ALL_ACTIONS, true));

        return $screens
            ->merge($fromRoutes)
            ->unique()
            ->values()
            ->all();
    }

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
        return [
            'user' => self::userPayload($user),
            'permissions' => self::permissionsForUser($user),
        ];
    }

    public static function permissionsForUser(User $user): array
    {
        $role = self::normalizedRole($user);

        $actions = self::permissionsForRole($role)['actions'] ?? [];

        $screenLevels = self::screenLevelsForUser($user);
        $screens = collect($screenLevels)
            ->filter(fn (string $level) => $level !== 'none')
            ->keys()
            ->values()
            ->all();

        return [
            'screens' => $screens,
            'screen_levels' => $screenLevels,
            'actions' => $actions,
        ];
    }

    /**
     * @return array<string, string> screen_id => access_level
     */
    private static function screenLevelsForUser(User $user): array
    {
        $role = self::normalizedRole($user);

        $screenIds = self::allScreenIds();
        $levels = [];

        foreach ($screenIds as $screenId) {
            $levels[$screenId] = self::defaultScreenAccessLevel($role, $screenId);
        }

        if ($role === 'super_admin') {
            return array_fill_keys($screenIds, 'full');
        }

        if (! Schema::hasTable('role_screen_permissions')) {
            return $levels;
        }

        $tenantId = (int) ($user->tenant_id ?? 0);
        if ($tenantId <= 0) {
            return $levels;
        }

        $rows = RoleScreenPermission::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('role', $role)
            ->get(['screen_id', 'access_level']);

        foreach ($rows as $row) {
            if (array_key_exists($row->screen_id, $levels)) {
                $levels[$row->screen_id] = (string) $row->access_level;
            }
        }

        return $levels;
    }

    private static function defaultScreenAccessLevel(string $role, string $screenId): string
    {
        if ($role === 'super_admin') {
            return 'full';
        }

        // Manager is allowed to see every screen by default.
        if ($role === 'manager') {
            return 'full';
        }

        // Preserve legacy defaults for existing roles so the app works out-of-the-box,
        // but allow the admin to override everything via the Permissions UI.
        return match ($role) {
            'receptionist' => match ($screenId) {
                'dashboard' => 'limited',
                'raw-receipt',
                'receipts-list',
                'gate-inquiries-list',
                'scale-notes-list',
                'settings',
                'delivery-order',
                'delivery-orders-list',
                'gate-inquiry',
                'scale-note',
                'reception-reports',
                'shipping-policy' => 'full',
                default => 'none',
            },
            'production_supervisor' => match ($screenId) {
                'dashboard' => 'limited',
                'production-order',
                'production-orders-list',
                'attendance',
                'production-order-view',
                'sort-record',
                'receipts-list',
                'settings' => 'full',
                default => 'none',
            },
            'export_officer' => match ($screenId) {
                'dashboard' => 'limited',
                'pallet-builder',
                'pallet',
                'shipping-policy',
                'settings' => 'full',
                default => 'none',
            },
            default => 'none',
        };
    }

    public static function defaultScreenAccessLevelForRole(string $role, string $screenId): string
    {
        return self::defaultScreenAccessLevel($role, $screenId);
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
                    'scale-notes-list',
                    'settings',
                    'delivery-order',
                    'delivery-orders-list',
                    'gate-inquiry',
                    'scale-note',
                    'reception-reports',
                ],
                'actions' => [],
            ],
            'production_supervisor' => [
                'screens' => [
                    'dashboard',
                    'production-order',
                    'production-orders-list',
                    'attendance',
                    'production-order-view',
                    'sort-record',
                    'receipts-list',
                    'settings',
                ],
                'actions' => [
                    'order.dispatch',
                    'order.pause',
                    'sort-record.post',
                ],
            ],
            'export_officer' => [
                'screens' => [
                    'dashboard',
                    'pallet-builder',
                    'pallet',
                    'shipping-policy',
                    'settings',
                ],
                'actions' => [
                    'pallet.create',
                    'pallet.update',
                    'pallet.delete',
                    'pallet.cooling',
                    'pallet.confirm',
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

        if (in_array($requiredPermission, self::ALL_ACTIONS, true)) {
            $profile = self::permissionsForRole($role);
            return in_array($requiredPermission, $profile['actions'], true);
        }

        $levels = self::screenLevelsForUser($user);
        return ($levels[$requiredPermission] ?? 'none') !== 'none';
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
            'App\\Http\\Controllers\\Api\\Reception\\ScaleNoteController@index' => 'scale-notes-list',
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
            'App\\Http\\Controllers\\Api\\Reception\\RawDeliveryOrderController@respond' => 'delivery-order',

            'App\\Http\\Controllers\\Api\\Settings\\PackhouseController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PackhouseController@store' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PackhouseController@update' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PackhouseController@destroy' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\BranchController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PayloadTemplateController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PayloadTemplateController@show' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\RawMaterialTypeController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\RawMaterialTypeController@store' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\RawMaterialTypeController@update' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\RawMaterialTypeController@destroy' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductController@store' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductController@update' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductController@destroy' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PalletTypeController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PalletTypeController@store' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PalletTypeController@update' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PalletTypeController@destroy' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\FridgeController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\FridgeController@store' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\FridgeController@update' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\FridgeController@destroy' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\PermissionsController@index' => 'permissions',
            'App\\Http\\Controllers\\Api\\Settings\\PermissionsController@update' => 'permissions',
            'App\\Http\\Controllers\\Api\\Settings\\PermissionsController@reset' => 'permissions',
            'App\\Http\\Controllers\\Api\\Settings\\ProductionLineController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductionLineController@store' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductionLineController@update' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductionLineController@destroy' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductionStageController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ContactController@index' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ContactController@store' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ContactController@update' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ContactController@destroy' => 'settings',
            'App\\Http\\Controllers\\Api\\Settings\\ProductionSupervisorController@index' => 'production-order',

            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@index' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@store' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@show' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@update' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@destroy' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@availableRawReceipts' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@dispatch' => 'order.dispatch',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@pause' => 'order.pause',
            'App\\Http\\Controllers\\Api\\Production\\ProductionOrderController@cancel' => 'order.cancel',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@index' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@store' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@show' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@update' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@destroy' => 'sort-record',
            'App\\Http\\Controllers\\Api\\Production\\SortRecordController@post' => 'sort-record.post',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@index' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@store' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@show' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@update' => 'production-order',
            'App\\Http\\Controllers\\Api\\Production\\AttendanceController@destroy' => 'production-order',
            'App\\Http\\Controllers\\Api\\Station\\UserManagementController@index' => 'production-order',
            'App\\Http\\Controllers\\Api\\Station\\UserManagementController@update' => 'user.manage',
            'App\\Http\\Controllers\\Api\\Station\\UserManagementController@destroy' => 'user.manage',

            'App\\Http\\Controllers\\Api\\Export\\PalletController@index' => 'pallet',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@store' => 'pallet.create',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@show' => 'pallet',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@update' => 'pallet.update',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@destroy' => 'pallet.delete',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@cooling' => 'pallet.cooling',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@confirmReceipt' => 'pallet.confirm',
            'App\\Http\\Controllers\\Api\\Export\\PalletController@availableOrders' => 'pallet',
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

            // Reception Reports (PDF + Excel)
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@rawReceiptsPdf' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@rawReceiptsExcel' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@gateInquiriesPdf' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@gateInquiriesExcel' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@scaleNotesPdf' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@scaleNotesExcel' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@deliveryOrdersPdf' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@deliveryOrdersExcel' => 'reception-reports',
            'App\\Http\\Controllers\\Api\\Reports\\ReceptionReportsController@dailyFullExcel' => 'reception-reports',

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
