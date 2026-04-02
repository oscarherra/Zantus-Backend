<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Busca al usuario por su correo, si no existe lo crea con estos datos
        User::updateOrCreate(
            ['email' => 'admin@zantu.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin1234*'),
                'role' => 'admin',
            ]
        );
    }
}