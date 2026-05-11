<?php

namespace App\Services\Stock;

class StockService
{
    public function inventory(): array
    {
        return [];
    }

    public function pricing(array $payload): array
    {
        return $payload;
    }

    public function dispatchShipment(array $payload): array
    {
        return $payload;
    }
}

