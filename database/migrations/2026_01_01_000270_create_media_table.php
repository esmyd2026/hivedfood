<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Path as stored in the records that use it: a path on the public
            // disk ("banners/x.png") or a root-relative asset ("/images/...").
            $table->string('path', 512)->unique();
            // public = uploaded and manageable, shipped = file living in /public
            $table->string('source')->default('public');
            $table->string('folder')->default('biblioteca');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt')->nullable();
            $table->text('description')->nullable();
            // Brand assets shipped with the project cannot be deleted.
            $table->boolean('is_protected')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['folder', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
