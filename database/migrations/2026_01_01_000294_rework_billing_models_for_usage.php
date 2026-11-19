<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las modalidades de cobro vuelven, pero sin solaparse con el catálogo.
 *
 * Una modalidad ahora solo decide dos cosas: si se cobra la mensualidad fija
 * y si además se cobra el consumo por pedidos. Todo lo demás —sucursales,
 * módulos, implementaciones— vive en el catálogo comercial y en un solo
 * sitio, para que dos pantallas no puedan configurar el mismo cargo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_models', function (Blueprint $table) {
            // Qué cobra la modalidad.
            $table->boolean('charges_monthly')->default(true)->after('currency');
            $table->boolean('charges_usage')->default(false)->after('charges_monthly');
            // Pedidos al mes sin cargo antes de empezar a contar tramos.
            $table->unsignedInteger('included_orders')->default(0)->after('charges_usage');
        });

        Schema::table('billing_models', function (Blueprint $table) {
            // Cargos que ahora pertenecen al catálogo: dejarlos aquí permitiría
            // cobrar dos veces la misma sucursal o la misma mensualidad.
            $table->dropColumn(['base_monthly', 'extra_branch_monthly', 'included_branches', 'requires_plan']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            // Consumo estimado del mes: variable, se factura aparte del fijo.
            $table->decimal('usage_monthly', 12, 2)->nullable()->after('recurring_total');
        });
    }

    public function down(): void
    {
        Schema::table('billing_models', function (Blueprint $table) {
            $table->dropColumn(['charges_monthly', 'charges_usage', 'included_orders']);
            $table->decimal('base_monthly', 12, 2)->nullable();
            $table->decimal('extra_branch_monthly', 12, 2)->nullable();
            $table->unsignedInteger('included_branches')->default(1);
            $table->boolean('requires_plan')->default(false);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('usage_monthly');
        });
    }
};
