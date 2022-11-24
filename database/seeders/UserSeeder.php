<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => env("ADMIN_NAME",'Admin'),
            'email' => env('ADMIN_EMAIL', 'admin@server.com'),
            'password' => Hash::make(env('ADMIN_PASSWORD','password')),
        ]);
    }
}
