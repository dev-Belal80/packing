# Pallets API (Export)

These endpoints are defined in `routes/api.php` under the protected route group:

- Auth: `sanctum` (Bearer token)
- Middleware: `tenant`, `subscription`, `api.permission`

## Auth header

All requests must include:

```
Authorization: Bearer <token>
Accept: application/json
```

## Permission keys (checked by `api.permission` middleware)

- Read/list/view + available orders: `pallet`
- Create: `pallet.create`
- Update: `pallet.update`
- Delete: `pallet.delete`
- Cooling: `pallet.cooling`
- Confirm receipt: `pallet.confirm`

> Note: Access is granted when the permission key exists in `permissions.screens` **or** `permissions.actions` (see `App\Support\ApiAccess::canAccess`).

---

## Endpoints

### List pallets

- `GET /api/export/pallets`
- Required permission: `pallet`
- Query params (all optional):
  - `status`
  - `date_from`
  - `date_to`
  - `packhouse_id`
  - `client_contact_id`
  - `product_id`
  - `production_line_id`
  - `search`
  - `per_page` (default `15`)

### Create pallet

- `POST /api/export/pallets`
- Required permission: `pallet.create`

### Show pallet

- `GET /api/export/pallets/{pallet}`
- Required permission: `pallet`

Response note (used by Edit Pallet form):
- This endpoint returns the **full pallet record** (all persisted fields + basic nested lookup objects when available), so the frontend can pre-fill the edit modal without extra requests.

### Update pallet

- `PUT /api/export/pallets/{pallet}`
- `PATCH /api/export/pallets/{pallet}`
- Required permission: `pallet.update`

### Delete pallet

- `DELETE /api/export/pallets/{pallet}`
- Required permission: `pallet.delete`

### Start / update cooling for pallet

- `POST /api/export/pallets/{pallet}/cooling`
- Required permission: `pallet.cooling`

### Confirm pallet receipt

- `POST /api/export/pallets/{pallet}/confirm-receipt`
- Required permission: `pallet.confirm`

### Available orders for building pallets (Sort Record Lines)

There are **two** routes pointing to the same controller method for frontend compatibility:

- `GET /api/export/pallets/available-orders`
- `GET /api/export/pallets-available-orders` (alias)

Required permission: `pallet`

Query params (all optional):
- `per_page` (default `25`)
- `lot_only` (boolean)
- `search`
- `packhouse_id`
- `product_id`
- `production_line_id`

Response note:
- Each item contains both `id` and `available_order_id` (same value) for frontend compatibility.
