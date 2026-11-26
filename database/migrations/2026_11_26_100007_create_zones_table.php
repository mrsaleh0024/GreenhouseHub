<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('greenhouse_id')->constrained()->restrictOnDelete();
            $t->string('code', 10);
            $t->string('name', 120);
            $t->foreignId('zone_type_id')->nullable()->constrained('catalog_items')->restrictOnDelete();
            $t->decimal('area_m2', 10, 2)->nullable();
            $t->unsignedInteger('capacity_plants')->nullable();
            $t->string('status', 20)->default('active');
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['greenhouse_id', 'code']);
            $t->index(['greenhouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
