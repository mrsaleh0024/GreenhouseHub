<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class CodeGenerator
{
    public function next(string $name, string $prefix): string
    {
        $n = DB::transaction(function () use ($name) {
            DB::table('code_sequences')->insertOrIgnore(['name' => $name, 'last_value' => 0, 'updated_at' => now()]);
            $row = DB::table('code_sequences')->where('name', $name)->lockForUpdate()->first();
            $next = $row->last_value + 1;
            DB::table('code_sequences')->where('name', $name)->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        }, 3);

        return sprintf('%s-%06d', $prefix, $n);
    }

    public function nextZone(int $greenhouseId): string
    {
        $next = DB::transaction(function () use ($greenhouseId) {
            DB::table('greenhouses')->where('id', $greenhouseId)->lockForUpdate()->firstOrFail();
            $name = 'zone:'.$greenhouseId;
            $maximum = 0;
            foreach (DB::table('zones')->where('greenhouse_id', $greenhouseId)->pluck('code') as $code) {
                if (preg_match('/^Z(\d+)$/', $code, $match)) {
                    $maximum = max($maximum, (int) $match[1]);
                }
            }
            DB::table('code_sequences')->insertOrIgnore(['name' => $name, 'last_value' => $maximum, 'updated_at' => now()]);
            $sequence = DB::table('code_sequences')->where('name', $name)->lockForUpdate()->first();
            $value = max((int) $sequence->last_value, $maximum) + 1;
            DB::table('code_sequences')->where('name', $name)->update(['last_value' => $value, 'updated_at' => now()]);

            return $value;
        }, 3);

        return sprintf('Z%02d', $next);
    }
}
