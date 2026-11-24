<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

        if (Schema::hasColumn('users', 'email')) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            }

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }

            $table->string('mobile', 11)->unique()->after('name');
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');
            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending')->after('mobile_verified_at');
            
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile',
                'mobile_verified_at',
                'status',
                'deleted_at'
            ]);

            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
        });
    }
};