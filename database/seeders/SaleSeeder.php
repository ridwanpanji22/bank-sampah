<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sale;
use Faker\Factory as Faker;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 2000; $i++) {
            $type_trash = $faker->randomElements(['plastik', 'kertas', 'besi', 'kaca', 'logam'], $faker->numberBetween(1, 5));
            $price = [];
            $weight = [];
            $total_weight = 0;

            foreach ($type_trash as $trash) {
                $price[] = $faker->numberBetween(3000, 10000);
                $weight[] = $faker->numberBetween(1000, 5000);
                $total_weight += end($weight);
            }

            Sale::create([
                'date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'name' => $faker->company,
                'type_trash' => json_encode($type_trash),
                'price' => json_encode($price),
                'weight' => json_encode($weight),
                'total_price' => array_sum($price) * $total_weight,
                'total_weight' => $total_weight,
            ]);
        }
    }
}
