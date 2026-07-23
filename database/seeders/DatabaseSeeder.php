<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 管理者アカウント
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'      => 'システム管理者',
                'password'  => \Illuminate\Support\Facades\Hash::make('Admin1234'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        // 編集者アカウント
        User::firstOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name'      => '編集者ユーザー',
                'password'  => \Illuminate\Support\Facades\Hash::make('Editor1234'),
                'role'      => 'editor',
                'is_active' => true,
            ]
        );

        // 閲覧者アカウント
        User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name'      => '閲覧者ユーザー',
                'password'  => \Illuminate\Support\Facades\Hash::make('Viewer1234'),
                'role'      => 'viewer',
                'is_active' => true,
            ]
        );
    }
}
