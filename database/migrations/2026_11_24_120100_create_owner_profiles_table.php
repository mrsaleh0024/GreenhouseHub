<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('greenhouse_name', 120);
            $table->string('province', 50);
            $table->string('city', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};
