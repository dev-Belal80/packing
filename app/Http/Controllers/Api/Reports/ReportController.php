<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Api\BaseApiController;

class ReportController extends BaseApiController
{
    public function dashboard()
    {
        return $this->success([
            'kpis' => [],
        ]);
    }

    public function receiptTracking(int $id)
    {
        return $this->success([
            'id' => $id,
            'timeline' => [],
        ]);
    }

    public function productionStats()
    {
        return $this->success([
            'stats' => [],
        ]);
    }

    public function palletTracking(int $id)
    {
        return $this->success([
            'id' => $id,
            'timeline' => [],
        ]);
    }

    public function shipments()
    {
        return $this->success([
            'shipments' => [],
        ]);
    }
}

