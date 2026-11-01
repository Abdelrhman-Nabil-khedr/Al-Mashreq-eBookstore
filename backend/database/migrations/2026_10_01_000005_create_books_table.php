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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('isbn', 20)->unique()->index();
            $table->string('title', 1000);
            $table->string('subtitle', 1000)->nullable();
            $table->string('series', 500)->nullable();
            $table->longText('synopsis')->nullable();
            $table->unsignedSmallInteger('pages')->nullable();
            $table->string('language', 50)->default('Arabic')->index();
            $table->string('age_range', 50)->nullable();
            $table->string('cover_path', 500)->nullable();
            $table->longText('search_vector')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();

            // Relationships
            $table->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('contributor_name', 500)->nullable();
            $table->string('contributor_role', 100)->nullable();
            $table->timestamps();

            // Indexes
            $table->fullText('title');
            $table->fullText('search_vector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
