# User Stories — Summary and Next Steps

**Updated:** 2026-05-17

## **Completed**
- **Route Mapping:** Added mapping for `SortRecordController@post` → `sort-record` in `app/Support/ApiAccess.php` so transfer/post no longer returns `route.unmapped`.
- **Permissions Alignment:** Removed `receipt.price` from `receptionist` and removed `order.cancel` from `production_supervisor` in `app/Support/ApiAccess.php` to match the lifecycle page expectations.
- **Seed Data / Demo Users:** Demo roles and users added/confirmed in `database/seeders/ClientTestSeeder.php` (examples: `production@demo.local`, `reception@demo.local`, `station.admin@demo.local`).
- **API Payload Examples:** Provided example JSON payloads for Sort Record create and edit actions for UI integration.

## **Recommended Next Stories (priority)**
- **Frontend: conditional UI controls**: show/hide `Post/Transfer` and other actions based on permissions returned from `Auth::me` (ApiAccess profile).
  - Acceptance: production users see `Post` only when `permissions` include `sort-record`.
- **Integration test: sort-record post**: add a test that calls `POST /api/production/sort-records/{id}/post` using the demo `production` user to cover success and forbidden cases.
  - Acceptance: test asserts 200 on allowed user and 403 when permission missing.
- **Seeder: grant demo permissions**: update `ClientTestSeeder` to explicitly set any DB-level permissions or role assignments that the UI depends on.
  - Acceptance: running `php artisan db:seed --class=ClientTestSeeder` leaves demo users able to perform intended flows.
- **ApiAccess audit**: scan `ApiAccess::routePermissions()` for unmapped controller actions and produce a report (or add mappings where appropriate).
  - Acceptance: zero `route.unmapped` responses for standard flows during QA.
- **UI errors & messaging**: improve client messages for 403/422 errors (translate and show actionable text).
  - Acceptance: user sees clear guidance when an action is blocked (e.g., "مطلوب إذن: sort-record").

## **How to test locally**

1. Seed demo data:

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=ClientTestSeeder
```

2. Authenticate as demo users (e.g. `production@demo.local`) via the app UI or API and verify `Auth::me` includes expected `permissions`.

3. Exercise the endpoint:

```bash
# replace {id} with a draft sort-record id
curl -X POST http://127.0.0.1:8000/api/production/sort-records/{id}/post \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json"
```

Expect 200 (posted) when permission exists, or 403 with `required_permission` when not.

## **Optional follow-ups I can do now**
- Add the frontend conditional (point to UI file you use). 
- Create the integration test for `sort-record post`.
- Run a full `ApiAccess` mapping audit and auto-suggest missing mappings.


---
File created: `docs/user-stories-summary.md`
