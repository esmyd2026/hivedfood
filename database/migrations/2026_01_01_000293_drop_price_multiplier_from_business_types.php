<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El tipo de negocio ya no altera el precio.
 *
 * La fórmula comercial es paquete + módulos + escalabilidad: un multiplicador
 * oculto por rubro contradice que el cliente entienda exactamente por qué paga
 * lo que paga, así que la columna desaparece en lugar de quedarse inerte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn('price_multiplier');
        });
    }

    public function down(): void
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->decimal('price_multiplier', 6, 3)->default(1)->after('description');
        });
    }
};
