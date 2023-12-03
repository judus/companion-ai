<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!User::where(['email' => 'julien.duseyau@gmail.com'])->first()) {
            User::create([
                'name' => 'Julien',
                'email' => 'julien.duseyau@gmail.com',
                'password' => Hash::make('password'),
            ]);
        }
    }
}
