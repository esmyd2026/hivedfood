<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->uuid('public_token')->unique();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_type_label')->nullable();
            $table->string('plan_label')->nullable();
            $table->string('currency', 8)->default('USD');
            $table->decimal('setup_subtotal', 12, 2)->default(0);
            $table->decimal('recurring_subtotal', 12, 2)->default(0);
            $table->decimal('setup_discount', 12, 2)->default(0);
            $table->decimal('recurring_discount', 12, 2)->default(0);
            $table->decimal('setup_total', 12, 2)->default(0);
            $table->decimal('recurring_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            // true when every selected item has a configured price
            $table->boolean('is_priced')->default(false);
            // new | sent | negotiating | accepted | rejected | expired
            $table->string('status')->default('new');
            $table->json('discounts')->nullable();
            $table->json('conditions')->nullable();
            $table->json('payload')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
