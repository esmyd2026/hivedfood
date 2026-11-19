<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo comercial de ChatbotGO.
 *
 * Cada módulo pasa a tener dos precios (implementación y mensualidad) en lugar
 * de uno solo con tipo de cobro, se incorporan las dependencias entre módulos
 * y los paquetes guardan qué módulos traen incluidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            $table->decimal('setup_price', 12, 2)->nullable()->after('icon');
            $table->decimal('monthly_price', 12, 2)->nullable()->after('setup_price');

            // Dependencias declaradas por slug de módulo.
            $table->json('requires')->nullable()->after('plan_slugs');
            $table->json('requires_any')->nullable()->after('requires');
            // Observaciones operativas que se muestran pero no bloquean.
            $table->json('notes')->nullable()->after('requires_any');
            $table->json('benefits')->nullable()->after('description');

            // Escalabilidad: unidades incluidas sin cargo y tipo de unidad.
            $table->unsignedInteger('included_quantity')->default(0)->after('max_quantity');
            $table->string('unit_kind')->nullable()->after('unit');
        });

        // Los precios que ya existían se reparten según su tipo de cobro.
        DB::table('pricing_items')->where('billing', 'one_time')->update([
            'setup_price' => DB::raw('price'),
        ]);

        DB::table('pricing_items')->where('billing', 'recurring')->update([
            'monthly_price' => DB::raw('price'),
        ]);

        Schema::table('pricing_items', function (Blueprint $table) {
            // Una sola fuente de verdad por precio: se retiran los antiguos.
            $table->dropColumn(['price', 'billing']);
        });

        Schema::table('pricing_plans', function (Blueprint $table) {
            // Nivel acumulativo: P1 = 1 … P5 = 5.
            $table->unsignedInteger('level')->default(1)->after('slug');
            // Módulos que el paquete trae incluidos, ya expandidos.
            $table->json('included_item_slugs')->nullable()->after('included');
        });

        Schema::table('quotations', function (Blueprint $table) {
            // Fotografía del tarifario usado, para que una subida de precios
            // no altere propuestas ya emitidas.
            $table->json('pricing_snapshot')->nullable()->after('payload');
            $table->foreignId('duplicated_from_id')->nullable()->after('lead_id')
                ->constrained('quotations')->nullOnDelete();
            $table->timestamp('implementation_requested_at')->nullable()->after('emailed_at');
        });

        // Las cotizaciones nacen como borrador.
        DB::table('quotations')->where('status', 'new')->update(['status' => 'draft']);

        // Historial de cambios del tarifario.
        Schema::create('pricing_audits', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('label')->nullable();
            // created | updated | deleted
            $table->string('action');
            $table->json('changes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_audits');

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicated_from_id');
            $table->dropColumn(['pricing_snapshot', 'implementation_requested_at']);
        });

        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn(['level', 'included_item_slugs']);
        });

        Schema::table('pricing_items', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable();
            $table->string('billing')->default('one_time');
        });

        Schema::table('pricing_items', function (Blueprint $table) {
            $table->dropColumn([
                'setup_price', 'monthly_price', 'requires', 'requires_any',
                'notes', 'benefits', 'included_quantity', 'unit_kind',
            ]);
        });
    }
};
