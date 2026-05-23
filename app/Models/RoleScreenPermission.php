<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class RoleScreenPermission extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'role',
        'screen_id',
        'access_level',
    ];

    /**
     * Default permissions matrix.
     *
     * @return array<int, array{0:string,1:string,2:string}>
     */
    public static function getDefaults(): array
    {
        return [
            ['manager', 'dashboard', 'full'],
            ['manager', 'raw-receipt', 'full'],
            ['manager', 'receipts-list', 'full'],
            ['manager', 'delivery-order', 'full'],
            ['manager', 'gate-inquiry', 'full'],
            ['manager', 'scale-note', 'full'],
            ['manager', 'production-order', 'full'],
            ['manager', 'sort-record', 'full'],
            ['manager', 'pallet-builder', 'full'],
            ['manager', 'shipping-policy', 'full'],
            ['manager', 'analytics', 'full'],
            ['manager', 'settings', 'full'],
            ['manager', 'permissions', 'full'],

            ['receptionist', 'dashboard', 'limited'],
            ['receptionist', 'raw-receipt', 'full'],
            ['receptionist', 'receipts-list', 'full'],
            ['receptionist', 'delivery-order', 'full'],
            ['receptionist', 'gate-inquiry', 'full'],
            ['receptionist', 'scale-note', 'full'],
            ['receptionist', 'shipping-policy', 'full'],

            ['production_supervisor', 'dashboard', 'limited'],
            ['production_supervisor', 'production-order', 'full'],
            ['production_supervisor', 'sort-record', 'full'],

            ['export_officer', 'dashboard', 'limited'],
            ['export_officer', 'pallet-builder', 'full'],
            ['export_officer', 'shipping-policy', 'full'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function screenIds(): array
    {
        $defaults = collect(self::getDefaults())
            ->map(fn (array $row) => $row[1])
            ->unique()
            ->values()
            ->all();

        return $defaults;
    }
}
