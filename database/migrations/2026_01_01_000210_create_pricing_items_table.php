<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // customization = step 3 of the quoter, addon = step 4
            $table->string('group')->default('customization');
            // one_time = implementation fee, recurring = monthly fee
            $table->string('billing')->default('one_time');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 8)->default('USD');
            $table->string('unit')->nullable();
            $table->boolean('allow_quantity')->default(false);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('max_quantity')->default(1);
            $table->boolean('is_included_by_default')->default(false);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('feature_id')->nullable()->constrained()->nullOnDelete();
            $table->json('plan_slugs')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['group', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_items');
    }
};
