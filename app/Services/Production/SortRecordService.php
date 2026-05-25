<?php

namespace App\Services\Production;

use App\Models\SortRecord;
use App\Models\SortRecordLine;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SortRecordService
{
    public function paginate(array $filters, int $perPage = 20)
    {
        return $this->baseQuery()
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('sort_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $date) => $q->whereDate('sort_date', '<=', $date))
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where('reference_no', 'like', "%{$term}%"))
            ->latest('sort_date')
            ->paginate($perPage);
    }

    public function findOrFail(int $id)
    {
        return $this->baseQuery()->findOrFail($id);
    }

    public function createWithLines(array $data, array $lines): SortRecord
    {
        return DB::transaction(function () use ($data, $lines) {
            if (! is_array($lines) || count($lines) === 0) {
                throw ValidationException::withMessages(['lines' => 'يجب إضافة خط واحد على الأقل للفرزة.']);
            }

            $data['status'] = $data['status'] ?? 'draft';
            $record = SortRecord::query()->create($data);

            foreach ($lines as $index => $line) {
                if (empty($line['production_order_id'])) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.production_order_id" => 'رقم أمر التشغيل مطلوب في كل سطر.',
                    ]);
                }

                $gradeA = (float) ($line['grade_a_kg'] ?? 0);
                $gradeB = (float) ($line['grade_b_kg'] ?? 0);
                $gradeC = (float) ($line['grade_c_kg'] ?? 0);
                $waste = (float) ($line['waste_kg'] ?? 0);
                $returned = (float) ($line['returned_kg'] ?? 0);

                foreach ([$gradeA, $gradeB, $gradeC, $waste, $returned] as $val) {
                    if ($val < 0) {
                        throw ValidationException::withMessages(["lines.{$index}" => 'القيم العددية لا يمكن أن تكون سالبة.']);
                    }
                }

                $computedTotal = $gradeA + $gradeB + $gradeC + $waste + $returned;

                SortRecordLine::query()->create([
                    'sort_record_id' => $record->id,
                    'raw_type' => $line['raw_type'],
                    'lot_no' => $line['lot_no'] ?? null,
                    'production_line_id' => $line['production_line_id'] ?? null,
                    'production_order_id' => $line['production_order_id'],
                    'grade_a_kg' => $gradeA,
                    'grade_b_kg' => $gradeB,
                    'grade_c_kg' => $gradeC,
                    'waste_kg' => $waste,
                    'returned_kg' => $returned,
                    'line_total' => $computedTotal,
                    'sort_order' => $index,
                ]);
            }

            // recalculate aggregate totals
            $record->recalculateTotals();

            return $record->load(['lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
        });
    }

    public function updateWithLines(SortRecord $record, array $data, ?array $lines): SortRecord
    {
        return DB::transaction(function () use ($record, $data, $lines) {
            $record->update($data);

            if (is_array($lines)) {
                $record->lines()->delete();

                if (count($lines) === 0) {
                    throw ValidationException::withMessages(['lines' => 'يجب إضافة خط واحد على الأقل للفرزة.']);
                }

                foreach ($lines as $index => $line) {
                    if (empty($line['production_order_id'])) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.production_order_id" => 'رقم أمر التشغيل مطلوب في كل سطر.',
                        ]);
                    }

                    $gradeA = (float) ($line['grade_a_kg'] ?? 0);
                    $gradeB = (float) ($line['grade_b_kg'] ?? 0);
                    $gradeC = (float) ($line['grade_c_kg'] ?? 0);
                    $waste = (float) ($line['waste_kg'] ?? 0);
                    $returned = (float) ($line['returned_kg'] ?? 0);

                    foreach ([$gradeA, $gradeB, $gradeC, $waste, $returned] as $val) {
                        if ($val < 0) {
                            throw ValidationException::withMessages(["lines.{$index}" => 'القيم العددية لا يمكن أن تكون سالبة.']);
                        }
                    }

                    $computedTotal = $gradeA + $gradeB + $gradeC + $waste + $returned;

                    SortRecordLine::query()->create([
                        'sort_record_id' => $record->id,
                        'raw_type' => $line['raw_type'],
                        'lot_no' => $line['lot_no'] ?? null,
                        'production_line_id' => $line['production_line_id'] ?? null,
                        'production_order_id' => $line['production_order_id'],
                        'grade_a_kg' => $gradeA,
                        'grade_b_kg' => $gradeB,
                        'grade_c_kg' => $gradeC,
                        'waste_kg' => $waste,
                        'returned_kg' => $returned,
                        'line_total' => $computedTotal,
                        'sort_order' => $index,
                    ]);
                }
            }

            // recalculate totals after updating lines
            $record = $record->fresh();
            $record->recalculateTotals();

            return $record->load(['lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
        });
    }

    public function delete(SortRecord $record): void
    {
        $record->lines()->delete();
        $record->delete();
    }

    public function post(SortRecord $record, int $userId): SortRecord
    {
        return DB::transaction(function () use ($record, $userId) {
            $record = SortRecord::query()
                ->with(['lines.productionOrder.rawReceipt', 'lines.productionLine', 'packhouse', 'postedBy', 'createdBy'])
                ->lockForUpdate()
                ->findOrFail($record->id);

            if ($record->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'الفرزة ليست في حالة مسودة.',
                ]);
            }

            if ($record->lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'لا يمكن عمل post لفرزة بدون خطوط.']);
            }

            $record->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            foreach ($record->lines as $line) {
                $productionOrder = $line->productionOrder;
                $rawReceipt = $productionOrder?->rawReceipt;

                if (! $productionOrder || ! $rawReceipt) {
                    throw ValidationException::withMessages([
                        "lines.{$line->id}" => 'سطر الفرزة لا يحتوي على أمر تشغيل صالح أو استلام خام صالح.',
                    ]);
                }

                $consumedQty = (float) $line->line_total;
                $finishedQty = (float) $line->grade_a_kg + (float) $line->grade_b_kg + (float) $line->grade_c_kg;
                $wasteQty = (float) $line->waste_kg;
                $returnedQty = (float) $line->returned_kg;

                // Ensure totals are consistent (finished + waste + returned == consumed)
                $sumParts = $finishedQty + $wasteQty + $returnedQty;
                if (abs($sumParts - $consumedQty) > 0.001) {
                    throw ValidationException::withMessages([
                        "lines.{$line->id}" => 'مجموع المنتجات والهالك والمرتجع لا يساوي إجمالي السطر.',
                    ]);
                }

                StockTransaction::query()->create([
                    'type' => 'out',
                    'reason' => 'sort_consumption',
                    'sort_record_line_id' => $line->id,
                    'sort_record_id' => $record->id,
                    'raw_receipt_id' => $rawReceipt->id,
                    'production_order_id' => $productionOrder->id,
                    'quantity_kg' => $consumedQty,
                    'created_by' => $userId,
                ]);

                if ($finishedQty > 0) {
                    StockTransaction::query()->create([
                        'type' => 'in',
                        'reason' => 'sort_finished_goods',
                        'sort_record_line_id' => $line->id,
                        'sort_record_id' => $record->id,
                        'raw_receipt_id' => $rawReceipt->id,
                        'production_order_id' => $productionOrder->id,
                        'quantity_kg' => $finishedQty,
                        'created_by' => $userId,
                    ]);
                }

                if ($wasteQty > 0) {
                    StockTransaction::query()->create([
                        'type' => 'out',
                        'reason' => 'sort_waste',
                        'sort_record_line_id' => $line->id,
                        'sort_record_id' => $record->id,
                        'raw_receipt_id' => $rawReceipt->id,
                        'production_order_id' => $productionOrder->id,
                        'quantity_kg' => $wasteQty,
                        'created_by' => $userId,
                    ]);
                }

                // Adjust raw receipt consumed/dispatched counters
                $returnedQty = (float) $line->returned_kg;
                $toConsume = max(0.0, $consumedQty - $returnedQty);
                $rawReceipt->consume($toConsume);

                // Mark production order completed if applicable
                if ($productionOrder->status !== 'completed') {
                    $productionOrder->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                }
            }

            return $record->fresh()->load(['lines.productionOrder.rawReceipt', 'lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
        });
    }

    private function baseQuery(): Builder
    {
        return SortRecord::query()->with(['lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
    }
}

