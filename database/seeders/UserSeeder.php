<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Create 500 customers
        for ($i = 1; $i <= 500; $i++) {
            $customer = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('12345678'), // Default password for simplicity
                'address' => $faker->address,
                'phone' => $faker->phoneNumber,
                'ccm' => $faker->regexify('[A-Za-z0-9]{15}'),
            ]);

            $customer->assignRole('customer');
        }

        // Create 10 drivers
        for ($i = 1; $i <= 10; $i++) {
            $driver = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('12345678'), // Default password for simplicity
                'address' => $faker->address,
                'phone' => $faker->phoneNumber,
            ]);

            $driver->assignRole('driver');
        }
    }
}
