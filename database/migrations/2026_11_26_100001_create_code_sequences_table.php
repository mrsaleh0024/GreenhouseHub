<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_sequences', function (Blueprint $t) {
            $t->string('name', 40)->primary();
            $t->unsignedBigInteger('last_value')->default(0);
            $t->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_sequences');
    }
};
