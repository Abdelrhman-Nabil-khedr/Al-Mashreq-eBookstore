<?php

namespace App\Console\Commands;

class ImportLegacyJson extends ImportLegacyData
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-legacy-json
                            {--path= : Custom path to the legacy books.json file}
                            {--covers-path= : Custom path to legacy covers directory}
                            {--force : Force import without prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Locate, decode, and migrate all books from the legacy static JSON file into the database with cover images and relationship resolution.';
}
