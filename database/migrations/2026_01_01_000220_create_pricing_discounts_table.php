<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            // percentage | fixed
            $table->string('type')->default('percentage');
            $table->decimal('value', 12, 2)->default(0);
            // setup | recurring | total
            $table->string('applies_to')->default('setup');
            $table->unsignedInteger('min_items')->default(0);
            $table->decimal('min_subtotal', 12, 2)->nullable();
            $table->json('plan_slugs')->nullable();
            $table->boolean('is_automatic')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_discounts');
    }
};
