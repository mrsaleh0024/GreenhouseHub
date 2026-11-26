<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greenhouse_options', function (Blueprint $t) {
            $t->foreignId('greenhouse_id')->constrained('greenhouses', 'id', 'go_greenhouse_fk')->cascadeOnDelete();
            $t->foreignId('catalog_item_id')->index()->constrained('catalog_items', 'id', 'go_catalog_fk')->restrictOnDelete();
            $t->timestamps();
            $t->primary(['greenhouse_id', 'catalog_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greenhouse_options');
    }
};
