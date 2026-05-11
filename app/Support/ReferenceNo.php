<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceNo
{
    public static function generate(string $table, string $prefix, int $tenantId, string $dateYmd): string
    {
        $prefixWithDate = $prefix.'-'.$dateYmd.'-';

        $last = DB::table($table)
            ->where('tenant_id', $tenantId)
            ->where('reference_no', 'like', $prefixWithDate.'%')
            ->orderByDesc('reference_no')
            ->value('reference_no');

        $next = 1;
        if (is_string($last) && Str::startsWith($last, $prefixWithDate)) {
            $tail = (int) substr($last, strlen($prefixWithDate));
            if ($tail > 0) {
                $next = $tail + 1;
            }
        }

        return $prefixWithDate.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

