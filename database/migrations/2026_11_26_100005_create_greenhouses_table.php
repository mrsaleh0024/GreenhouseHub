<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greenhouses', function (Blueprint $t) {
            $t->id();
            $t->string('greenhouse_code', 20)->unique();
            $t->foreignId('customer_id')->index()->constrained('customers', 'id', 'gh_customer_fk')->restrictOnDelete();
            $t->foreignId('responsible_expert_id')->nullable()->index()->constrained('users', 'id', 'gh_expert_fk')->nullOnDelete();
            $t->string('name', 150);
            $t->foreignId('province_id')->nullable()->constrained('catalog_items', 'id', 'gh_province_fk')->restrictOnDelete();
            $t->foreignId('city_id')->nullable()->constrained('catalog_items', 'id', 'gh_city_fk')->restrictOnDelete();
            $t->text('address')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->smallInteger('altitude_m')->nullable();
            $t->decimal('area_m2', 10, 2)->nullable();
            $t->foreignId('structure_type_id')->nullable()->constrained('catalog_items', 'id', 'gh_structure_fk')->restrictOnDelete();
            $t->foreignId('cultivation_type_id')->nullable()->constrained('catalog_items', 'id', 'gh_cultivation_fk')->restrictOnDelete();
            $t->date('commissioned_on')->nullable();
            $t->string('status', 20)->default('active')->index();
            $t->text('notes')->nullable();
            $t->unsignedTinyInteger('completeness_percent')->default(0);
            $t->string('search_text', 300)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users', 'id', 'gh_created_fk')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users', 'id', 'gh_updated_fk')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index('province_id');
            $t->index('structure_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greenhouses');
    }
};
