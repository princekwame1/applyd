<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@applydacademy.com'],
            [
                'name' => 'Applyd Admin',
                'password' => Hash::make('bootcamp2026'),
            ]
        );

        $admin->syncRoles(['super']);
    }
}
