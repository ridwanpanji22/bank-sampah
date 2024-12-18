<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'ktp' => '1234567890123456',
            'password' => bcrypt('12345678'),
            'address' => 'Gang Jambul',
            'phone' => '123456789',
        ]);

        $admin->assignRole('admin');
    }
}
