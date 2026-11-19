<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // monthly = tarifa fija | per_order = por pedido | commission = % de ventas
            $table->string('type');
            $table->string('headline')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('currency', 8)->default('USD');

            // Cargo fijo que puede acompañar a cualquier modelo (plataforma).
            $table->decimal('base_monthly', 12, 2)->nullable();
            // Mínimo mensual a facturar, si el modelo lo tiene.
            $table->decimal('min_monthly', 12, 2)->nullable();
            // Porcentaje sobre ventas, solo para el modelo por comisión.
            $table->decimal('commission_percent', 6, 3)->nullable();

            // El volumen que define el tramo se cuenta por sucursal o en total.
            $table->string('volume_basis')->default('branch');
            // Sucursales incluidas y precio de cada sucursal adicional.
            $table->unsignedInteger('included_branches')->default(1);
            $table->decimal('extra_branch_monthly', 12, 2)->nullable();

            // Cuando es true, el cotizador pide además elegir un paquete.
            $table->boolean('requires_plan')->default(false);

            $table->json('included')->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('order_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_model_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->unsignedInteger('from_orders')->default(1);
            // null = sin tope superior
            $table->unsignedInteger('to_orders')->nullable();
            // Cuatro decimales: el precio por pedido se mueve en centavos.
            $table->decimal('price_per_order', 10, 4);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['billing_model_id', 'from_orders'], 'tier_model_from_idx');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('billing_model_id')->nullable()->after('pricing_plan_id')->constrained()->nullOnDelete();
            $table->string('billing_model_label')->nullable()->after('plan_label');
        });

        // El precio por pedido se mueve en céntimos: hacen falta 4 decimales
        // para que importe unitario x cantidad cuadre con el subtotal.
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_model_id');
            $table->dropColumn('billing_model_label');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->change();
        });

        Schema::dropIfExists('order_price_tiers');
        Schema::dropIfExists('billing_models');
    }
};
