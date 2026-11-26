<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultivation_cycles', function (Blueprint $t) {
            $t->id();
            $t->string('cycle_code', 20)->unique();
            $t->foreignId('greenhouse_id')->constrained()->restrictOnDelete();
            $t->foreignId('zone_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('crop_id')->constrained('catalog_items')->restrictOnDelete();
            $t->foreignId('variety_id')->nullable()->constrained('catalog_items')->restrictOnDelete();
            $t->foreignId('substrate_id')->nullable()->constrained('catalog_items')->restrictOnDelete();
            $t->date('planting_date');
            $t->date('expected_harvest_date')->nullable();
            $t->date('harvest_started_on')->nullable();
            $t->date('harvest_ended_on')->nullable();
            $t->unsignedInteger('plant_count')->nullable();
            $t->decimal('planted_area_m2', 10, 2)->nullable();
            $t->string('status', 20)->default('planned');
            $t->decimal('total_yield_kg', 12, 2)->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['greenhouse_id', 'status']);
            $t->index('zone_id');
            $t->index('crop_id');
            $t->index('planting_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultivation_cycles');
    }
};
