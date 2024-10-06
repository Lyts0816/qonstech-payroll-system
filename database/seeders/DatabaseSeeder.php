<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'John Doe',
            'email' => 'mackmondejar@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('123'),
            'remember_token' => null,
            'Username' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
            'role' => 1,

        ]);

        DB::table('roles')->insert([
            'name' => 'Admin',
            'Username' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),

        ]);
    }
}
