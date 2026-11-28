<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->string('source_ref', 100)->nullable()->after('slug');
            $table->string('location_context', 150)->nullable()->after('label_en');
            $table->boolean('location_ambiguous')->default(false)->after('location_context');
            $table->string('identity_key', 255)->default('')->after('normalized_label');
        });

        DB::table('catalog_items')->update(['identity_key' => DB::raw('normalized_label')]);

        // Existing system cities already carry their stable source id in the slug.
        foreach (DB::table('catalog_items')->where('type', 'city')->where('slug', 'like', 'iran-%')->get(['id', 'slug', 'normalized_label']) as $city) {
            $sourceRef = substr($city->slug, 5);
            if ($sourceRef === '' || ! ctype_digit($sourceRef)) {
                continue;
            }

            DB::table('catalog_items')->where('id', $city->id)->update([
                'source_ref' => $sourceRef,
                'identity_key' => $city->normalized_label.'@'.$sourceRef,
            ]);
        }

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropUnique('catalog_type_parent_label_unique');
            $table->unique(['type', 'parent_key', 'identity_key'], 'catalog_type_parent_identity_unique');
            $table->unique(['type', 'source_ref'], 'catalog_type_source_ref_unique');
            $table->index(['type', 'parent_key', 'normalized_label'], 'catalog_type_parent_label_index');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropUnique('catalog_type_parent_identity_unique');
            $table->dropUnique('catalog_type_source_ref_unique');
            $table->dropIndex('catalog_type_parent_label_index');
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn(['source_ref', 'location_context', 'location_ambiguous', 'identity_key']);
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->unique(['type', 'parent_key', 'normalized_label'], 'catalog_type_parent_label_unique');
        });
    }
};
