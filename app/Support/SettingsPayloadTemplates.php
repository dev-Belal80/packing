<?php

namespace App\Support;

final class SettingsPayloadTemplates
{
    public static function all(): array
    {
        return [
            'contacts' => [
                'resource' => 'contacts',
                'method' => 'POST',
                'endpoint' => '/api/settings/contacts',
                'read_only' => false,
                'required_fields' => ['name'],
                'optional_fields' => ['phone', 'email', 'national_id', 'address', 'type', 'tags', 'notes', 'is_active'],
                'payload_template' => [
                    'name' => 'Supplier A',
                    'phone' => '+201000000000',
                    'email' => 'supplier@example.com',
                    'national_id' => '29801011234567',
                    'address' => 'Cairo',
                    'type' => 'company',
                    'tags' => ['supplier'],
                    'notes' => 'Primary supplier',
                    'is_active' => true,
                ],
            ],
            'fridges' => [
                'resource' => 'fridges',
                'method' => 'POST',
                'endpoint' => '/api/settings/fridges',
                'read_only' => false,
                'required_fields' => ['packhouse_id', 'name'],
                'optional_fields' => ['capacity_tons', 'min_temp', 'max_temp', 'is_active'],
                'payload_template' => [
                    'packhouse_id' => 1,
                    'name' => 'Fridge A',
                    'capacity_tons' => 20,
                    'min_temp' => 1,
                    'max_temp' => 4,
                    'is_active' => true,
                ],
            ],
            'packhouses' => [
                'resource' => 'packhouses',
                'method' => 'POST',
                'endpoint' => '/api/settings/packhouses',
                'read_only' => false,
                'required_fields' => ['name'],
                'optional_fields' => ['code', 'location', 'is_active'],
                'payload_template' => [
                    'code' => 'PKH-001',
                    'name' => 'Main Packhouse',
                    'location' => 'Giza',
                    'is_active' => true,
                ],
            ],
            'pallet-types' => [
                'resource' => 'pallet-types',
                'method' => 'POST',
                'endpoint' => '/api/settings/pallet-types',
                'read_only' => false,
                'required_fields' => ['name'],
                'optional_fields' => ['max_cartons', 'max_weight_kg', 'is_active'],
                'payload_template' => [
                    'name' => 'Euro Pallet',
                    'max_cartons' => 80,
                    'max_weight_kg' => 1200,
                    'is_active' => true,
                ],
            ],
            'products' => [
                'resource' => 'products',
                'method' => 'POST',
                'endpoint' => '/api/settings/products',
                'read_only' => false,
                'required_fields' => ['name'],
                'optional_fields' => ['code', 'unit', 'carton_weight_kg', 'grades', 'min_cooling_hours', 'waste_threshold_pct', 'is_active'],
                'payload_template' => [
                    'name' => 'Orange',
                    'code' => 'PRD-001',
                    'unit' => 'kg',
                    'carton_weight_kg' => 20,
                    'grades' => ['A', 'B'],
                    'min_cooling_hours' => 8,
                    'waste_threshold_pct' => 5,
                    'is_active' => true,
                ],
            ],
            'production-lines' => [
                'resource' => 'production-lines',
                'method' => 'POST',
                'endpoint' => '/api/settings/production-lines',
                'read_only' => false,
                'required_fields' => ['packhouse_id', 'name'],
                'optional_fields' => ['status', 'is_active'],
                'payload_template' => [
                    'packhouse_id' => 1,
                    'name' => 'Line 1',
                    'status' => 'running',
                    'is_active' => true,
                ],
            ],
            'permissions' => [
                'resource' => 'permissions',
                'method' => 'PUT',
                'endpoint' => '/api/settings/permissions',
                'read_only' => false,
                'required_fields' => ['permissions'],
                'optional_fields' => [],
                'payload_template' => [
                    'permissions' => [
                        [
                            'role' => 'manager',
                            'screen_id' => 'settings',
                            'access_level' => 'full',
                        ],
                        [
                            'role' => 'receptionist',
                            'screen_id' => 'raw-receipt',
                            'access_level' => 'full',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function find(string $resource): ?array
    {
        return self::all()[$resource] ?? null;
    }
}