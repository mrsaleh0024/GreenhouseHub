<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class CodeGenerator
{
    public function next(string $name, string $prefix): string
    {
        $n = DB::transaction(function () use ($name) {
            $row = DB::table('code_sequences')->where('name', $name)->lockForUpdate()->first();
            if (! $row) {
                DB::table('code_sequences')->insert(['name' => $name, 'last_value' => 1, 'updated_at' => now()]);

                return 1;
            } $next = $row->last_value + 1;
            DB::table('code_sequences')->where('name', $name)->update(['last_value' => $next, 'updated_at' => now()]);

            return $next;
        });

        return sprintf('%s-%06d', $prefix, $n);
    }
}
