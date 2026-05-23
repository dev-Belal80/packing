# Create Pallet (Modal) → API Payload Mapping

This document explains exactly what the frontend **Create Pallet** modal should send to the backend when creating a new pallet.

Endpoint:
- `POST /api/export/pallets`

Related (Edit Pallet):
- To edit an existing pallet, preload the modal by calling `GET /api/export/pallets/{pallet}`.
- The response is designed to include **all saved pallet fields** using the same keys as this document, so the UI can bind it directly to the form state.

## 1) Key concept: using `available_order_id`

The modal has a table/list of **Available Orders** (Sort Record Lines). When the user selects a row, the frontend should send:

- `available_order_id`: the selected row id (SortRecordLine id)

When `available_order_id` (or `sort_record_line_id`) is present, the backend will try to infer core fields automatically (so the frontend doesn’t need to guess IDs).

Backend inference (if it can load the line):
- `sort_record_id`
- `packhouse_id`
- `production_line_id`
- `production_order_id`
- `product_id`
- `order_number` (falls back to production order reference or sort record reference)
- `pallet_date` (falls back to sort record date)

So in the **normal frontend flow**, the modal should always select an Available Order row and send `available_order_id`.

---

## 2) Minimum payload (recommended)

If you selected an available order row:

```json
{
  "available_order_id": 1,
  "pallet_type_id": 1,
  "pallet_date": "2026-05-19",
  "cartons_count": 30,
  "actual_weight": 600,
  "net_weight": 594.5,
  "grade": "A",
  "status": "building"
}
```

Notes:
- `pallet_type_id` is required.
- `pallet_date` is required (if missing, backend will default it, but frontend should still send it).
- `product_id` / `sort_record_id` / `packhouse_id` are usually inferred from `available_order_id`.

---

## 3) Full example payload (all fields)

```json
{
  "available_order_id": 1,

  "reference_no": "DS255",
  "wooden_pallet_no": "WP-12",
  "order_number": "ORD-1001",
  "final_order_no": "FIN-1001",
  "branch": "الرئيسي",
  "pallet_date": "2026-05-19",
  "pallet_time": "09:30",

  "pallet_type_id": 1,
  "client_contact_id": 1,
  "client_code": "38345",
  "supplier_contact_id": 2,

  "lot_no": "LOT-0055",
  "fridge_id": 1,

  "raw_type": "Orange",
  "package_type": "Carton",
  "size": "4.5 kg",
  "grade": "A",
  "storage_location": "A-1",

  "actual_weight": 600,
  "net_weight": 594.5,
  "total_weight_kg": 600,

  "cooling_start": "2026-05-19 05:30:00",
  "cooling_end": "2026-05-19 08:30:00",

  "stickers": "Main sticker",
  "customer_lot_no": "C-9001",
  "has_carton": true,
  "has_punnet": false,
  "no_label": false,
  "original_pallet_ref": "DS0001",
  "special_specs": "Keep upright",

  "cartons_count": 30,
  "status": "building"
}
```

---

## 4) Modal field → JSON key mapping

Use this as the exact mapping for your form state.

### A) Selected Available Order row (from the table)
- Row id → `available_order_id`

### B) Header / identity
- Pallet reference → `reference_no` (optional)
- Wooden pallet number → `wooden_pallet_no` (optional)
- Order number → `order_number` (optional; usually inferred)
- Final order number → `final_order_no` (optional)
- Branch → `branch` (optional)

### C) Date/time
- Pallet date (required) → `pallet_date`
- Pallet time (optional) → `pallet_time` (format `HH:mm`)

### D) Lookups (select inputs)
- Pallet type (required) → `pallet_type_id`
- Client → `client_contact_id` (optional)
- Supplier → `supplier_contact_id` (optional)
- Fridge → `fridge_id` (optional)

### E) Product details
- Lot No → `lot_no` (optional)
- Raw type → `raw_type` (optional)
- Package type → `package_type` (optional)
- Size → `size` (optional)
- Grade → `grade` (optional)
- Storage location → `storage_location` (optional)

### F) Weights & cartons
- Cartons count → `cartons_count` (optional integer)
- Actual weight → `actual_weight` (optional number)
- Net weight → `net_weight` (optional number)
- Total weight → `total_weight_kg` (optional number)

Important backend behavior:
- The model will normalize `total_weight_kg` to match `actual_weight` during saving.

### G) Cooling dates
- Cooling start → `cooling_start` (optional datetime)
- Cooling end → `cooling_end` (optional datetime)

If you need a **cooling record** (PalletCooling row), call after create:
- `POST /api/export/pallets/{pallet}/cooling`

### H) Stickers / extra
- Stickers → `stickers` (optional)
- Customer lot no → `customer_lot_no` (optional)
- Original pallet reference → `original_pallet_ref` (optional)
- Special specs → `special_specs` (optional)
- Client code → `client_code` (optional)

### I) Booleans
- Has carton checkbox → `has_carton`
- Has punnet checkbox → `has_punnet`
- No label checkbox → `no_label`

### J) Status
- Status → `status`

Allowed values (backend validation):
- `building`, `built`, `cooling`, `cooled`, `confirmed`, `shipped`

---

## 5) What the frontend should NOT try to infer

When using `available_order_id`, do **not** guess these IDs in the UI:
- `sort_record_id`
- `production_order_id`
- `production_line_id`
- `product_id`
- `packhouse_id`

Let the backend infer them from the chosen available order row.

---

## 6) Troubleshooting

If `GET /api/export/pallets/available-orders` returns empty (`data: []`):
- Ensure there are `SortRecordLine` rows whose related `SortRecord.status` is `posted`.
- Ensure your filters match the seeded values (especially `production_line_id`).
