<?php

namespace Database\Seeders;

use App\Models\Price;
use Illuminate\Database\Seeder;

class PriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        Price::firstOrCreate(
            ['en' => "STANDARD"],
            [
                'pt' => "PADRAO",
                'min' => 500,
                'max' => 30000,
                'amount' => 11,
            ]
        );
        Price::firstOrCreate(
            ['en' => "BRONZE"],
            [
                'pt' => "BRONZE",
                'min' => 30001,
                'max' => 60000,
                'amount' => 9,
            ]
        );
        Price::firstOrCreate(
            ['en' => "SILVER"],
            [
                'pt' => "PRATA",
                'min' => 60001,
                'max' => 100000,
                'amount' => 8,
            ]
        );
        Price::firstOrCreate(
            ['en' => "GOLD"],
            [
                'pt' => "OURO",
                'min' => 100001,
                'max' => 500000,
                'amount' => 7,
            ]
        );
        Price::firstOrCreate(
            ['en' => "PREMIUM"],
            [
                'pt' => "PREMIUM",
                'min' => 500001,
                'max' => 1000000,
                'amount' => 7,
            ]
        );
    }
}
