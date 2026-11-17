<?php

namespace Database\Seeders;

use App\Models\Prefix;
use Illuminate\Database\Seeder;

class PrefixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        Prefix::firstOrCreate(
            ["label" => 91],
            ["network_id" => 1]
        );
        Prefix::firstOrCreate(
            ["label" => 99],
            ["network_id" => 1]
        );
        Prefix::firstOrCreate(
            ["label" => 92],
            ["network_id" => 2]
        );
        Prefix::firstOrCreate(
            ["label" => 93],
            ["network_id" => 2]
        );
        Prefix::firstOrCreate(
            ["label" => 94],
            ["network_id" => 2]
        );
        Prefix::firstOrCreate(
            ["label" => 97],
            ["network_id" => 2]
        );
        Prefix::firstOrCreate(
            ["label" => 95],
            ["network_id" => 3]
        );
    }
}
