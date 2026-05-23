# Sort Record Frontend Guide

This guide explains how the frontend should handle the Sort Record (sorting) feature.

## Endpoints

- Create: POST /api/production/sort-records
- Show: GET /api/production/sort-records/{id}
- Update: PATCH /api/production/sort-records/{id}
- Post (finalize): POST /api/production/sort-records/{id}/post
- List: GET /api/production/sort-records?status=draft&date_from=2026-05-01&date_to=2026-05-16&search=SR-

## Create Payload (single item)

```json
{
  "packhouse_id": 1,
  "sort_date": "2026-05-16",
  "sort_time": "08:30",
  "accounting_period": "2026-05",
  "branch": "الرئيسي",
  "description_ar": "فرزة يومية",
  "description_en": "Daily sort",
  "notes": "single line",
  "lines": [
    {
      "raw_type": "بطاطس",
      "lot_no": "10022",
      "production_line_id": 3,
      "production_order_id": 12,
      "grade_a_kg": 0,
      "grade_b_kg": 391,
      "grade_c_kg": 0,
      "waste_kg": 0,
      "returned_kg": 0
    }
  ]
}
```

## Update Payload

- Same fields as create.
- If you send `lines`, you must send the full list (the backend replaces all lines).

## Totals (frontend calculation)

For each line:

```
line_total = grade_a_kg + grade_b_kg + grade_c_kg + waste_kg + returned_kg
```

For the header totals:

```
total_grade_a = sum(lines.grade_a_kg)
total_grade_b = sum(lines.grade_b_kg)
total_grade_c = sum(lines.grade_c_kg)
total_waste = sum(lines.waste_kg)
total_returned = sum(lines.returned_kg)
total_sort = total_grade_a + total_grade_b + total_grade_c + total_waste + total_returned
```

The backend also calculates totals, but computing them in the UI helps show live totals.

## UI Rules

- If `status` is `posted`, disable edit and delete actions.
- Do not allow posting if there are no lines or if `total_sort` is 0.
- Always send `lines` as an array, even for a single item.

## Response Shape

The API response returns:

- Header fields (reference_no, branch, sort_date, ...)
- Totals (total_grade_a, total_sort, ...)
- Lines array with line details and line_total

## Notes

- The API is multi-tenant and uses tenant context from auth middleware.
- For listing filters, use `status`, `date_from`, `date_to`, and `search`.
