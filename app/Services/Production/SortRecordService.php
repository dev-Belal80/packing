<?php

namespace App\Services\Production;

use App\Models\SortRecord;
use App\Models\SortRecordLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
            $record = SortRecord::query()->create($data);

            foreach ($lines as $index => $line) {
                SortRecordLine::query()->create([
                    'sort_record_id' => $record->id,
                    'raw_type' => $line['raw_type'],
                    'lot_no' => $line['lot_no'] ?? null,
                    'production_line_id' => $line['production_line_id'] ?? null,
                    'production_order_id' => $line['production_order_id'] ?? null,
                    'grade_a_kg' => $line['grade_a_kg'],
                    'grade_b_kg' => $line['grade_b_kg'],
                    'grade_c_kg' => $line['grade_c_kg'],
                    'waste_kg' => $line['waste_kg'],
                    'returned_kg' => $line['returned_kg'] ?? 0,
                    'sort_order' => $index,
                ]);
            }

            return $record->load(['lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
        });
    }

    public function updateWithLines(SortRecord $record, array $data, ?array $lines): SortRecord
    {
        return DB::transaction(function () use ($record, $data, $lines) {
            $record->update($data);

            if (is_array($lines)) {
                $record->lines()->delete();

                foreach ($lines as $index => $line) {
                    SortRecordLine::query()->create([
                        'sort_record_id' => $record->id,
                        'raw_type' => $line['raw_type'],
                        'lot_no' => $line['lot_no'] ?? null,
                        'production_line_id' => $line['production_line_id'] ?? null,
                        'production_order_id' => $line['production_order_id'] ?? null,
                        'grade_a_kg' => $line['grade_a_kg'],
                        'grade_b_kg' => $line['grade_b_kg'],
                        'grade_c_kg' => $line['grade_c_kg'],
                        'waste_kg' => $line['waste_kg'],
                        'returned_kg' => $line['returned_kg'] ?? 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $record->fresh()->load(['lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
        });
    }

    public function delete(SortRecord $record): void
    {
        $record->lines()->delete();
        $record->delete();
    }

    public function post(SortRecord $record, int $userId): SortRecord
    {
        $record->update([
            'status' => 'posted',
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);

        return $record->fresh()->load(['lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
    }

    private function baseQuery(): Builder
    {
        return SortRecord::query()->with(['lines.productionLine', 'packhouse', 'postedBy', 'createdBy']);
    }
}

