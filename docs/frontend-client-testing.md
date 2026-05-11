# Frontend Client Testing Guide (Local)

This project provides seeded demo data and two auth flows:

- **Bearer-token auth (recommended for React SPA on a different origin like `127.0.0.1:3000`)**
- **Sanctum cookie/session auth (requires CSRF cookie and `withCredentials`)**

If you only want to test “all features from the frontend client side”, use the **Bearer-token flow**.

---

## 1) Prerequisites

- PHP + Composer installed
- MySQL running and `.env` points to your local DB
- The app server running on `http://127.0.0.1:8000`

---

## 2) Prepare the database (seed demo tenant + data)

This will create demo tenant data and users used by the UI.

```bash
php artisan migrate:fresh --seed
```

Seeded demo credentials (created by `ClientTestSeeder`):

- Station admin: `station.admin@demo.local` / `password`
- Reception user: `reception@demo.local` / `password`
- Production user: `production@demo.local` / `password`

A SYSTEM super admin is also created:

- `superadmin@example.com` / `password`

---

## 3) Start the Laravel API

If you use the built-in dev server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Quick health check:

```bash
curl -s http://127.0.0.1:8000/api/health
```

Expected output: `{ "ok": true }`

---

## 4) Auth (Recommended): Bearer token flow

### 4.1 Get a token

Use the token endpoint:

`POST /api/auth/token`

Example:

```bash
curl -s -X POST http://127.0.0.1:8000/api/auth/token \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  --data '{
    "email": "station.admin@demo.local",
    "password": "password",
    "device_name": "react"
  }'
```

Response includes:

- `token_type`: `Bearer`
- `token`: the API token you must send on future requests
- `user`: the authenticated user

### 4.2 Call protected endpoints

All protected API routes require:

- `Authorization: Bearer <token>`

Example:

```bash
curl -s http://127.0.0.1:8000/api/auth/me \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer <PASTE_TOKEN_HERE>"
```

Important tenancy note:

- Protected routes run `tenant` middleware.
- Tenant is taken from the authenticated user’s `tenant_id`.
- You **do not** need to send a tenant header; it is inferred from the user.

Logout note:

- Use `POST /api/auth/token/logout` for bearer-token sessions. It does not require a CSRF cookie.
- Use `POST /api/auth/logout` only for the cookie/session flow, which does require CSRF handling.

---

## 5) If you see `419 CSRF token mismatch`

### What it means

A **419** from Laravel usually means the server expected a **CSRF token** (cookie/session flow), but the request didn’t include a valid one.

### How to avoid it

- If you are using the **Bearer token flow** (`/api/auth/token`):
  - Don’t send cookie-session auth.
  - Just send the `Authorization` header.

- If you are using the **cookie/session flow** (`/api/auth/login`):
  1) First call `GET /sanctum/csrf-cookie`
  2) Then call `POST /api/auth/login`
  3) Ensure your frontend HTTP client sends cookies (`withCredentials: true`)

In Axios (frontend), cookie flow usually needs:

- `withCredentials: true`
- `xsrfCookieName: 'XSRF-TOKEN'`
- `xsrfHeaderName: 'X-XSRF-TOKEN'`

If your frontend is running on a different origin (e.g. `127.0.0.1:3000`), **Bearer-token flow is simpler**.

---

## 6) Feature checklist (API areas to test from the UI)

All of these are under protected routes (require Bearer token):

### Reception
- Gate Inquiries: `GET/POST /api/reception/gate-inquiries`
- Scale Notes: `GET/POST /api/reception/scale-notes`
- Raw Receipts: `GET/POST /api/reception/raw-receipts`
- Approve Raw Receipt: `PATCH /api/reception/raw-receipts/{id}/approve`

### Production
- Orders: `GET/POST /api/production/orders`
- Dispatch: `POST /api/production/orders/{id}/dispatch`
- Pause: `POST /api/production/orders/{id}/pause`
- Cancel: `POST /api/production/orders/{id}/cancel`
- Sort Records: `GET/POST /api/production/sort-records`
- Attendance: `GET/POST /api/production/attendance`

### Export
- Pallets: `GET/POST /api/export/pallets`
- Cooling: `POST /api/export/pallets/{id}/cooling`
- Confirm receipt: `POST /api/export/pallets/{id}/confirm-receipt`
- Shipping Policies: `GET/POST /api/export/shipping-policies`
- Approve Shipping Policy: `PATCH /api/export/shipping-policies/{id}/approve`

### Stock
- Inventory: `GET /api/stock/inventory`
- Pricing: `POST /api/stock/pricing`
- Dispatch shipment: `POST /api/stock/dispatch-shipment`

### Reports
- Dashboard: `GET /api/reports/dashboard`
- Receipt tracking: `GET /api/reports/receipt-tracking/{id}`
- Production stats: `GET /api/reports/production-stats`
- Pallet tracking: `GET /api/reports/pallet-tracking/{id}`
- Shipments: `GET /api/reports/shipments`

---

## 7) Quick Postman setup (optional)

A Postman collection exists:

- `postman/packing-api.postman_collection.json`

Use the request:

- **Bearer Token Auth (Sanctum tokens) → Auth - Token Login**

Then set the returned token into your Authorization header as:

- `Bearer {{BEARER_TOKEN}}`

---

## 8) Troubleshooting

- If protected routes return `403 Tenant not found`:
  - Your user has no `tenant_id`.
  - Use one of the seeded demo users (e.g. `station.admin@demo.local`).

- If mutating requests return `403` with Arabic message about read-only mode:
  - Tenant subscription expired (`subscription_ends_at` in DB is past).
  - Update the tenant subscription date in the database for local testing.

- After changing routes/middleware, clear caches:

```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```
