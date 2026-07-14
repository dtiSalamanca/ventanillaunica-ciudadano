<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Usuario Admin',
            'email' => 'cthjxir@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
