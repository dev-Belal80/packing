<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\RoleScreenPermission;
use App\Support\ApiAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PermissionsController extends BaseApiController
{
    public function index(Request $request)
    {
        $tenantId = app()->has('current_tenant_id') ? (int) app('current_tenant_id') : (int) ($request->user()?->tenant_id ?? 0);

        if ($tenantId <= 0) {
            return $this->error('Tenant context required.', 422);
        }

        $screenIds = collect(ApiAccess::allScreenIds())->values();

        $roles = collect(['manager', 'receptionist', 'production_supervisor', 'export_officer']);

        $matrix = $this->defaultMatrix($screenIds->all());

        if (Schema::hasTable('role_screen_permissions')) {
            $rows = RoleScreenPermission::query()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->get(['role', 'screen_id', 'access_level']);

            foreach ($rows as $row) {
                if (! isset($matrix[$row->role])) {
                    continue;
                }

                if (! array_key_exists($row->screen_id, $matrix[$row->role])) {
                    continue;
                }

                $matrix[$row->role][$row->screen_id] = (string) $row->access_level;
            }
        }

        $matrix['super_admin'] = [];
        foreach ($screenIds as $screenId) {
            $matrix['super_admin'][$screenId] = 'full';
        }

        return $this->success($matrix);
    }

    public function update(Request $request)
    {
        $tenantId = app()->has('current_tenant_id') ? (int) app('current_tenant_id') : (int) ($request->user()?->tenant_id ?? 0);

        if ($tenantId <= 0) {
            return $this->error('Tenant context required.', 422);
        }

        if (! Schema::hasTable('role_screen_permissions')) {
            return $this->error('Permissions table missing. Run migrations first.', 422);
        }

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*.role' => ['required', 'string', 'in:manager,receptionist,production_supervisor,export_officer'],
            'permissions.*.screen_id' => ['required', 'string', 'max:255'],
            'permissions.*.access_level' => ['required', 'string', 'in:full,limited,none'],
        ]);

        $screenIds = ApiAccess::allScreenIds();

        foreach ($validated['permissions'] as $perm) {
            if (! in_array($perm['screen_id'], $screenIds, true)) {
                continue;
            }

            $default = ApiAccess::defaultScreenAccessLevelForRole($perm['role'], $perm['screen_id']);

            if ($perm['access_level'] === $default) {
                RoleScreenPermission::query()
                    ->withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenantId)
                    ->where('role', $perm['role'])
                    ->where('screen_id', $perm['screen_id'])
                    ->delete();

                continue;
            }

            RoleScreenPermission::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'role' => $perm['role'],
                    'screen_id' => $perm['screen_id'],
                ],
                [
                    'access_level' => $perm['access_level'],
                ]
            );
        }

        return $this->success(null, 'تم حفظ الصلاحيات بنجاح');
    }

    public function reset(Request $request)
    {
        $tenantId = app()->has('current_tenant_id') ? (int) app('current_tenant_id') : (int) ($request->user()?->tenant_id ?? 0);

        if ($tenantId <= 0) {
            return $this->error('Tenant context required.', 422);
        }

        if (! Schema::hasTable('role_screen_permissions')) {
            return $this->error('Permissions table missing. Run migrations first.', 422);
        }

        RoleScreenPermission::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->delete();

        return $this->success(null, 'تم إعادة تعيين الصلاحيات للافتراضية');
    }

    private function defaultMatrix(array $screenIds): array
    {
        $roles = ['manager', 'receptionist', 'production_supervisor', 'export_officer'];
        $matrix = [];

        foreach ($roles as $role) {
            $matrix[$role] = [];
            foreach ($screenIds as $screenId) {
                $matrix[$role][$screenId] = ApiAccess::defaultScreenAccessLevelForRole($role, $screenId);
            }
        }

        return $matrix;
    }
}
