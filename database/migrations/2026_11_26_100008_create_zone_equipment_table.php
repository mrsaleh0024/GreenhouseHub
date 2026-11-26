<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_equipment', function (Blueprint $t) {
            $t->id();
            $t->foreignId('zone_id')->constrained('zones', 'id', 'ze_zone_fk')->cascadeOnDelete();
            $t->foreignId('equipment_id')->index()->constrained('catalog_items', 'id', 'ze_equipment_fk')->restrictOnDelete();
            $t->unsignedSmallInteger('quantity')->default(1);
            $t->string('notes', 255)->nullable();
            $t->timestamps();
            $t->unique(['zone_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_equipment');
    }
};
