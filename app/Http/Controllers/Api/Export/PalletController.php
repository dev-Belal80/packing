<?php

namespace App\Http\Controllers\Api\Export;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Export\PalletDetailsResource;
use App\Http\Resources\Export\PalletResource;
use App\Models\Pallet;
use App\Models\PalletType;
use App\Models\SortRecord;
use App\Models\SortRecordLine;
use App\Services\Export\PalletService;
use Illuminate\Http\Request;

class PalletController extends BaseApiController
{
    public function __construct(private readonly PalletService $service)
    {
    }

    public function index(Request $request)
    {
        $paginator = $this->service->paginate($request->only([
            'status',
            'date_from',
            'date_to',
            'packhouse_id',
            'client_contact_id',
            'product_id',
            'production_line_id',
            'search',
        ]), $request->integer('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => PalletResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->normalizeCreatePayload($request);
        $data = $request->merge($data)->validate($this->rules());

        $model = $this->service->create($data);
        return $this->success(new PalletDetailsResource($model), 'Pallet created', 201);
    }

    public function show(Pallet $pallet)
    {
        $model = $this->service->findOrFail($pallet);
        return $this->success(new PalletDetailsResource($model));
    }

    public function update(Request $request, Pallet $pallet)
    {
        if ($pallet->is_shipped || $pallet->status === 'shipped') {
            return $this->error('Forbidden', 403, null, 'pallet.update');
        }

        $data = $request->validate($this->rules(true));

        $model = $this->service->update($pallet, $data);
        return $this->success(new PalletDetailsResource($model), 'Updated');
    }

    public function destroy(Pallet $pallet)
    {
        if ($pallet->is_shipped || $pallet->status === 'shipped') {
            return $this->error('Forbidden', 403, null, 'pallet.delete');
        }

        $this->service->delete($pallet);
        return $this->success(['ok' => true], 'Deleted');
    }

    public function cooling(Request $request, Pallet $pallet)
    {
        $data = $request->validate([
            'fridge_id' => ['required', 'exists:fridges,id'],
            'cooling_start' => ['nullable', 'date'],
            'cooling_end' => ['nullable', 'date'],
            'entry_temp' => ['nullable', 'numeric'],
            'has_temp_alert' => ['nullable', 'boolean'],
        ]);

        $model = $this->service->cooling($pallet, $data);
        return $this->success(new PalletDetailsResource($model), 'Cooling started');
    }

    public function confirmReceipt(Pallet $pallet)
    {
        $model = $this->service->confirmReceipt($pallet);
        return $this->success(new PalletDetailsResource($model), 'receipt_confirmed');
    }

    public function availableOrders(Request $request)
    {
        $paginator = $this->service->availableOrders([
            'lot_only' => $request->boolean('lot_only'),
            'packhouse_id' => $request->integer('packhouse_id') ?: null,
            'product_id' => $request->integer('product_id') ?: null,
            'production_line_id' => $request->integer('production_line_id') ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ], $request->integer('per_page', 25));

        $data = collect($paginator->items())->map(function ($line): array {
            $product = $line->productionOrder?->product;
            $cartonWeight = (float) ($product?->carton_weight_kg ?? 1);
            $availableCartons = $cartonWeight > 0
                ? (int) floor(((float) $line->line_total) / $cartonWeight)
                : 0;

            return [
                'id' => $line->id,
                'available_order_id' => $line->id,
                'reference_no' => $line->productionOrder?->reference_no,
                'created_at' => $line->sortRecord?->created_at?->format('Y-m-d\TH:i:s'),
                'lot_no' => $line->lot_no,
                'production_line' => $line->productionLine?->name,
                'product' => $product?->name,
                'order_number' => $line->sortRecord?->reference_no,
                'available_cartons' => $availableCartons,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function rules(bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return [
            'packhouse_id' => [$required, 'exists:packhouses,id'],
            'sort_record_id' => [$required, 'exists:sort_records,id'],
            'sort_record_line_id' => ['sometimes', 'exists:sort_record_lines,id'],
            'available_order_id' => ['sometimes', 'exists:sort_record_lines,id'],
            'reference_no' => ['nullable', 'string', 'max:50'],
            'wooden_pallet_no' => ['nullable', 'string', 'max:100'],
            'order_number' => ['nullable', 'string', 'max:100'],
            'final_order_no' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:100'],
            'pallet_date' => [$required, 'date'],
            'pallet_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'production_line_id' => ['nullable', 'exists:production_lines,id'],
            'pallet_type_id' => [$required, 'exists:pallet_types,id'],
            'client_contact_id' => ['nullable', 'exists:contacts,id'],
            'client_code' => ['nullable', 'string', 'max:100'],
            'product_id' => [$required, 'exists:products,id'],
            'supplier_contact_id' => ['nullable', 'exists:contacts,id'],
            'lot_no' => ['nullable', 'string', 'max:100'],
            'fridge_id' => ['nullable', 'exists:fridges,id'],
            'brand_id' => ['nullable', 'integer', 'min:1'],
            'punnet_sticker_id' => ['nullable', 'integer', 'min:1'],
            'raw_type' => ['nullable', 'string', 'max:255'],
            'package_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:100'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'actual_weight' => ['nullable', 'numeric', 'gt:0'],
            'net_weight' => ['nullable', 'numeric', 'min:0'],
            'total_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'cooling_start' => ['nullable', 'date'],
            'cooling_end' => ['nullable', 'date'],
            'stickers' => ['nullable', 'string', 'max:255'],
            'customer_lot_no' => ['nullable', 'string', 'max:255'],
            'has_carton' => ['nullable', 'boolean'],
            'has_punnet' => ['nullable', 'boolean'],
            'no_label' => ['nullable', 'boolean'],
            'original_pallet_ref' => ['nullable', 'string', 'max:255'],
            'special_specs' => ['nullable', 'string'],
            'production_order_id' => ['nullable', 'exists:production_orders,id'],
            'cartons_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'max:50', 'in:building,built,cooling,cooled,confirmed,shipped'],
        ];
    }

    private function normalizeCreatePayload(Request $request): array
    {
        $data = $request->all();
        $lineId = $data['sort_record_line_id'] ?? $data['available_order_id'] ?? null;
        $line = $lineId
            ? SortRecordLine::query()->with(['sortRecord', 'productionLine', 'productionOrder.product'])->find($lineId)
            : $this->resolveSortRecordLineFromPayload($data);

        if ($line) {
            $data['sort_record_id'] = $data['sort_record_id'] ?? $line->sort_record_id;
            $data['production_line_id'] = $data['production_line_id'] ?? $line->production_line_id;
            $data['production_order_id'] = $data['production_order_id'] ?? $line->production_order_id;
            $data['product_id'] = $data['product_id'] ?? $line->productionOrder?->product_id;
            $data['packhouse_id'] = $data['packhouse_id'] ?? $line->sortRecord?->packhouse_id;
            $data['order_number'] = $data['order_number'] ?? $line->productionOrder?->reference_no ?? $line->sortRecord?->reference_no;
            $data['pallet_date'] = $data['pallet_date'] ?? $line->sortRecord?->sort_date?->format('Y-m-d') ?? now()->toDateString();
        }

        if (empty($data['packhouse_id']) && ! empty($data['sort_record_id'])) {
            $packhouseId = SortRecord::query()
                ->whereKey($data['sort_record_id'])
                ->value('packhouse_id');

            if ($packhouseId) {
                $data['packhouse_id'] = $packhouseId;
            }
        }

        if (empty($data['pallet_type_id'])) {
            $data['pallet_type_id'] = PalletType::query()
                ->orderBy('id')
                ->value('id');
        }

        if (empty($data['pallet_date'])) {
            $data['pallet_date'] = now()->toDateString();
        }

        if (empty($data['branch'])) {
            $data['branch'] = 'الرئيسي';
        }

        if (empty($data['status'])) {
            $data['status'] = 'building';
        }

        return $data;
    }

    private function resolveSortRecordLineFromPayload(array $data): ?SortRecordLine
    {
        $query = SortRecordLine::query()
            ->with(['sortRecord', 'productionLine', 'productionOrder.product'])
            ->whereHas('sortRecord', function ($sortRecordQuery): void {
                $sortRecordQuery->where('status', 'posted');
            });

        if (! empty($data['sort_record_id'])) {
            $query->where('sort_record_id', $data['sort_record_id']);
        }

        if (! empty($data['packhouse_id'])) {
            $query->whereHas('sortRecord', function ($sortRecordQuery) use ($data): void {
                $sortRecordQuery->where('packhouse_id', $data['packhouse_id']);
            });
        }

        if (! empty($data['production_line_id'])) {
            $query->where('production_line_id', $data['production_line_id']);
        }

        if (! empty($data['product_id'])) {
            $query->whereHas('productionOrder', function ($productionOrderQuery) use ($data): void {
                $productionOrderQuery->where('product_id', $data['product_id']);
            });
        }

        if (! empty($data['lot_no'])) {
            $query->where('lot_no', $data['lot_no']);
        }

        if (! empty($data['order_number'])) {
            $query->where(function ($nested) use ($data): void {
                $nested->whereHas('sortRecord', function ($sortRecordQuery) use ($data): void {
                    $sortRecordQuery->where('reference_no', $data['order_number']);
                })->orWhereHas('productionOrder', function ($productionOrderQuery) use ($data): void {
                    $productionOrderQuery->where('reference_no', $data['order_number']);
                });
            });
        }

        return $query->latest('id')->first();
    }
}

