<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('customer_code', 20)->unique();
            $t->string('full_name', 150);
            $t->string('customer_type', 10)->default('individual');
            $t->string('company_name', 200)->nullable();
            $t->string('national_id', 20)->nullable()->index();
            $t->string('mobile', 11)->unique();
            $t->string('phone', 20)->nullable();
            $t->string('email', 190)->nullable();
            $t->foreignId('province_id')->nullable()->constrained('catalog_items')->restrictOnDelete();
            $t->foreignId('city_id')->nullable()->constrained('catalog_items')->restrictOnDelete();
            $t->text('address')->nullable();
            $t->string('status', 20)->default('active')->index();
            $t->string('registered_via', 20)->default('staff');
            $t->text('notes')->nullable();
            $t->string('search_text', 600)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
