<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class RawDeliveryOrder extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'packhouse_id', 'reference_no', 'year', 'branch', 'order_date',
        'agricultural_season', 'work_order', 'cost_center', 'raw_type',
        'loading_warehouse', 'destination_warehouse', 'supplying_station',
        'delivery_station', 'loading_warehouse_season', 'supply_warehouse_season',
        'description_ar', 'description_en', 'reference_number',
        'weight_on_entry', 'weight_on_exit', 'total_quantity',
        'client_contact_id', 'sale_order', 'received_qty',
        'discount_pct', 'discount_qty', 'extra_discount_pct', 'extra_discount_qty',
        'invoice_number', 'net_qty', 'price_per_unit', 'total_amount',
        'units_count', 'sorting_cost', 'sorting_cost_per_ton',
        'other_expenses', 'supply_expenses',
        'supplier_contact_id', 'supply_order', 'supply_season',
        'supply_qty', 'supply_discount_pct', 'supply_discount_qty',
        'net_supply_qty', 'supplied_qty', 'cost_price', 'total_cost',
        'supply_units_count',
        'transport_contractor', 'transport_units', 'transport_unit_cost',
        'transport_total', 'transport_discount_qty', 'transport_price',
        'transport_discount_value',
        'status', 'created_by', 'confirmed_by', 'confirmed_at',
        'supplier_response', 'supplier_responded_by', 'supplier_responded_at', 'supplier_notes',
    ];

    protected $casts = [
        'order_date'     => 'date',
        'confirmed_at'   => 'datetime',
    ];

    // ─── Auto reference_no ─────────────────────────────
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $year  = now()->year;
            $count = static::withoutGlobalScopes()
                ->where('tenant_id', $model->tenant_id)
                ->whereYear('created_at', $year)
                ->count() + 1;
            $model->reference_no = sprintf('%04d/%d', $count, $year); // e.g. 0042/2026
            $model->year         = $year;
            if (!$model->created_by) {
                $model->created_by   = Auth::id();
            }
        });
    }

    // ─── Relationships ─────────────────────────────────
    public function client()    { return $this->belongsTo(Contact::class, 'client_contact_id'); }
    public function supplier()  { return $this->belongsTo(Contact::class, 'supplier_contact_id'); }
    public function packhouse() { return $this->belongsTo(Packhouse::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    // ─── Auto-calc helpers (called in Service) ─────────
    public static function calculate(array $data): array
    {
        $entry = $data['weight_on_entry'] ?? 0;
        $exit  = $data['weight_on_exit']  ?? 0;

        // Weight
        $data['total_quantity'] = max(0, $entry - $exit);

        // Sale section
        $received        = $data['received_qty']        ?? 0;
        $discPct         = $data['discount_pct']        ?? 0;
        $extraDiscPct    = $data['extra_discount_pct']  ?? 0;
        $discQty         = $received * ($discPct / 100);
        $extraDiscQty    = $received * ($extraDiscPct / 100);
        $netQty          = $received - $discQty - $extraDiscQty;
        $price           = $data['price_per_unit']      ?? 0;
        $totalAmt        = $netQty * $price;
        $sortingCostTon  = $netQty > 0 ? (($data['sorting_cost'] ?? 0) / ($netQty / 1000)) : 0;

        $data['discount_qty']           = round($discQty, 3);
        $data['extra_discount_qty']     = round($extraDiscQty, 3);
        $data['net_qty']                = round($netQty, 3);
        $data['total_amount']           = round($totalAmt, 2);
        $data['sorting_cost_per_ton']   = round($sortingCostTon, 4);

        // Supply section
        $supplyQty     = $data['supply_qty']          ?? 0;
        $supplyDiscPct = $data['supply_discount_pct'] ?? 0;
        $supplyDisc    = $supplyQty * ($supplyDiscPct / 100);
        $netSupply     = $supplyQty - $supplyDisc;
        $costPrice     = $data['cost_price']          ?? 0;
        $totalCost     = $netSupply * $costPrice;

        $data['supply_discount_qty'] = round($supplyDisc, 3);
        $data['net_supply_qty']      = round($netSupply, 3);
        $data['total_cost']          = round($totalCost, 2);

        // Transport section
        $tUnits     = $data['transport_units']     ?? 0;
        $tUnitCost  = $data['transport_unit_cost'] ?? 0;
        $tDiscQty   = $data['transport_discount_qty'] ?? 0;
        $tPrice     = $data['transport_price']     ?? 0;
        $tTotal     = $tUnits * $tUnitCost;
        $tDiscVal   = $tDiscQty * $tPrice;

        $data['transport_total']          = round($tTotal, 2);
        $data['transport_discount_value'] = round($tDiscVal, 2);

        return $data;
    }
}


