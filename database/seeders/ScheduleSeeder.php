<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Schedule;
use Faker\Factory as Faker;
use App\Models\User;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $customers = User::role('customer')->get();
        $drivers = User::role('driver')->pluck('id')->toArray();

        foreach ($customers as $customer) {
            for ($i = 1; $i <= 12; $i++) {
                Schedule::create([
                    'user_id_customer' => $customer->id,
                    'user_id_driver' => $faker->randomElement($drivers),
                    'number_order' => $customer->name . '-' . $faker->unique()->bothify('????-####'),
                    'pickup_date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                    'pickup_time' => $faker->time,
                    'status' => $faker->randomElement(['completed', 'pending', 'on the way']),
                ]);
            }
        }
    }
}
