<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Schedule;
use Faker\Factory as Faker;

class TransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $schedules = Schedule::all();

        foreach ($schedules as $schedule) {
            $type_trash = $faker->randomElements(['plastik', 'kertas', 'besi', 'kaca', 'logam'], $faker->numberBetween(1, 5));
            $price = [];
            $weight = [];

            foreach ($type_trash as $trash) {
                $price[] = $faker->numberBetween(1000, 5000);
                $weight[] = $faker->numberBetween(1, 20);
            }

            $transaction = Transaction::create([
                'date' => $schedule->pickup_date,
                'schedule_id' => $schedule->id,
                'type_trash' => json_encode($type_trash),
                'price' => json_encode($price),
                'weight' => json_encode($weight),
                'total_price' => array_sum($price) * array_sum($weight),
            ]);

            $users = [$schedule->user_id_driver, $schedule->user_id_customer];
            $transaction->users()->sync($users, true);
        }
    }
}
