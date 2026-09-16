<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'XHRfKIAtY6',
            'username' => 'superadmin',
            'email' => 'superadmin@mail.com',
            'password' => Hash::make('secret'),
            'is_admin' => true,
            'role_id' => 1,
            'lang' => 'en'
        ]);
    }
}
