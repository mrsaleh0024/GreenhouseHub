<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $t) {
            $t->id();
            $t->string('type', 40);
            $t->foreignId('parent_id')->nullable()->constrained('catalog_items')->restrictOnDelete();
            $t->unsignedBigInteger('parent_key')->default(0);
            $t->string('slug', 100);
            $t->string('label_fa', 150);
            $t->string('label_en', 150)->nullable();
            $t->string('normalized_label', 150);
            $t->string('status', 20)->default('approved');
            $t->string('source', 10)->default('system');
            $t->boolean('is_common')->default(false);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->json('meta')->nullable();
            $t->foreignId('merged_into_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['type', 'slug']);
            $t->unique(['type', 'parent_key', 'normalized_label'], 'catalog_type_parent_label_unique');
            $t->index(['type', 'status', 'sort_order']);
            $t->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
