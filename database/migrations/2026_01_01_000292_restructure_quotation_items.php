<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada línea de la cotización lleva implementación y mensualidad, como la
 * propuesta comercial que ve el cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('setup_price', 12, 2)->nullable()->after('description');
            $table->decimal('monthly_price', 12, 2)->nullable()->after('setup_price');
            // Lo que ya viene en el paquete se lista pero no se cobra.
            $table->boolean('is_included')->default(false)->after('is_priced');
            // Activado por una dependencia, no elegido por el cliente.
            $table->boolean('is_auto_added')->default(false)->after('is_included');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'subtotal', 'billing']);
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->string('billing')->default('one_time');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['setup_price', 'monthly_price', 'is_included', 'is_auto_added']);
        });
    }
};
