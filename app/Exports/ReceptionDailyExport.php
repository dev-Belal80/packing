<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReceptionDailyExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $tenantId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
    ) {
    }

    public function sheets(): array
    {
        return [
            new RawReceiptsExport($this->tenantId, $this->dateFrom, $this->dateTo),
            new GateInquiriesExport($this->tenantId, $this->dateFrom, $this->dateTo),
            new ScaleNotesExport($this->tenantId, $this->dateFrom, $this->dateTo),
            new DeliveryOrdersExport($this->tenantId, $this->dateFrom, $this->dateTo),
        ];
    }
}
