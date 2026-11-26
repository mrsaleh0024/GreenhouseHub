<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('owner_profiles');
    }

    public function down(): void
    {
        Schema::create('owner_profiles', function ($t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('greenhouse_name', 120);
            $t->string('province', 50);
            $t->string('city', 80);
            $t->timestamps();
        });
    }
};
