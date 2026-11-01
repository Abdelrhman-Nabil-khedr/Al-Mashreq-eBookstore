<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique()->index();
            $table->string('title', 500);
            $table->string('subtitle', 1000)->nullable();
            $table->longText('content')->nullable();
            $table->string('title_en', 500)->nullable();
            $table->string('subtitle_en', 1000)->nullable();
            $table->longText('content_en')->nullable();
            $table->string('meta_title', 500)->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
