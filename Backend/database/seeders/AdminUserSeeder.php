<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@terrasana.com',
            'password' => Hash::make('password123'), // Le mot de passe
            'type_id' => 3, // 3 = Administrateur
        ]);
    }
}