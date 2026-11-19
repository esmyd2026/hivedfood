<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->foreignId('business_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_type_label')->nullable();
            // quotation | demo_request | contact_form | whatsapp | newsletter
            $table->string('source')->default('contact_form');
            // new | contacted | qualified | proposal | won | lost
            $table->string('status')->default('new');
            $table->text('message')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('accepts_marketing')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
