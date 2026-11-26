<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete()->index();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $t->boolean('is_primary')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_user');
    }
};
