<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin123!'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'cliente@example.com'],
            [
                'name' => 'Cliente Demo',
                'password' => Hash::make('Cliente123!'),
                'role' => 'customer',
            ]
        );

        $this->call([
            ProductSeeder::class,
        ]);
    }
}
