<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('customer_user')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new \RuntimeException(
                'Duplicate customer-user relationships detected.'
            );
        }

        Schema::table('customer_user', function (Blueprint $table) {
            $table->unique('user_id', 'customer_user_user_id_unique');
        });
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $hasSupportingIndex = collect(
                Schema::getIndexes('customer_user')
            )->contains(fn (array $index) =>
                ! $index['unique']
                && ($index['columns'][0] ?? null) === 'user_id'
            );

            if (! $hasSupportingIndex) {
                Schema::table('customer_user', function (Blueprint $table) {
                    $table->index('user_id', 'customer_user_user_id_lookup');
                });
            }
        }

        Schema::table('customer_user', function (Blueprint $table) {
            $table->dropUnique('customer_user_user_id_unique');
        });
    }
};