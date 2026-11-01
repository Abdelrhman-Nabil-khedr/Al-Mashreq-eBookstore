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
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'illustrator')) {
                $table->string('illustrator', 500)->nullable()->after('contributor_role');
            }
            if (!Schema::hasColumn('books', 'translator')) {
                $table->string('translator', 500)->nullable()->after('illustrator');
            }
            if (!Schema::hasColumn('books', 'editor')) {
                $table->string('editor', 500)->nullable()->after('translator');
            }
            if (!Schema::hasColumn('books', 'series_position')) {
                $table->string('series_position', 50)->nullable()->after('series');
            }
            if (!Schema::hasColumn('books', 'bisac_code')) {
                $table->string('bisac_code', 100)->nullable()->after('series_position');
            }
            if (!Schema::hasColumn('books', 'wgs_code')) {
                $table->string('wgs_code', 100)->nullable()->after('bisac_code');
            }
            if (!Schema::hasColumn('books', 'thema_code')) {
                $table->string('thema_code', 100)->nullable()->after('wgs_code');
            }
            if (!Schema::hasColumn('books', 'catalog_code')) {
                $table->string('catalog_code', 100)->nullable()->after('thema_code');
            }
            if (!Schema::hasColumn('books', 'original_language')) {
                $table->string('original_language', 100)->nullable()->after('language');
            }
            if (!Schema::hasColumn('books', 'publication_date')) {
                $table->date('publication_date')->nullable()->after('pages');
            }
            if (!Schema::hasColumn('books', 'format')) {
                $table->string('format', 100)->nullable()->after('publication_date');
            }
            if (!Schema::hasColumn('books', 'author_bio')) {
                $table->text('author_bio')->nullable()->after('synopsis');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $columnsToDrop = [
                'illustrator',
                'translator',
                'editor',
                'series_position',
                'bisac_code',
                'wgs_code',
                'thema_code',
                'catalog_code',
                'original_language',
                'publication_date',
                'format',
                'author_bio',
            ];

            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('books', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
