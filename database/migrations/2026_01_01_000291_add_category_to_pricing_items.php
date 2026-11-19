<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            // Agrupa los módulos en el paso de personalización del cotizador.
            $table->string('category')->nullable()->after('group');
            $table->index(['group', 'category', 'sort_order'], 'items_group_category_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            $table->dropIndex('items_group_category_idx');
            $table->dropColumn('category');
        });
    }
};
