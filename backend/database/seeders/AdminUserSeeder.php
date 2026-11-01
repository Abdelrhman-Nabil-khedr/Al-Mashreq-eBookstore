<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@almashreqebookstore.com'],
            [
                'name' => 'مدير مكتبة المشرق',
                'password' => Hash::make('Admin@Mashreq2026!'),
                'role' => 'super_admin',
                'preferred_lang' => 'ar',
                'email_verified_at' => now(),
            ]
        );

        // Optional editor account for content managers
        User::updateOrCreate(
            ['email' => 'editor@almashreqebookstore.com'],
            [
                'name' => 'محرر المحتوى',
                'password' => Hash::make('Editor@Mashreq2026!'),
                'role' => 'editor',
                'preferred_lang' => 'ar',
                'email_verified_at' => now(),
            ]
        );
    }
}
