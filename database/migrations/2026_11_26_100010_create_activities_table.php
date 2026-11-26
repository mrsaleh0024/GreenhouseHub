<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->timestamp('occurred_at')->useCurrent();
            $t->foreignId('actor_id')->nullable()->index()->constrained('users', 'id', 'activity_actor_fk')->nullOnDelete();
            $t->string('actor_role', 30)->nullable();
            $t->string('event', 40)->index();
            $t->string('subject_type', 100);
            $t->unsignedBigInteger('subject_id');
            $t->foreignId('customer_id')->nullable()->constrained('customers', 'id', 'activity_customer_fk')->nullOnDelete();
            $t->foreignId('greenhouse_id')->nullable()->constrained('greenhouses', 'id', 'activity_greenhouse_fk')->nullOnDelete();
            $t->string('description', 255)->nullable();
            $t->json('changes')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->index(['subject_type', 'subject_id']);
            $t->index(['customer_id', 'occurred_at']);
            $t->index(['greenhouse_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
