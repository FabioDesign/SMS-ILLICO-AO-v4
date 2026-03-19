<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void{
        try {
            $this->call([
                // Réseaux
                NetworkSeeder::class,
                // Préfixe
                PrefixSeeder::class,
                // Type SMS
                SmsTypeSeeder::class,
                // Type de compte
                AccountTypeSeeder::class,
                // Ville
                TownSeeder::class,
                // Tarif
                PriceSeeder::class,
            ]);
        } catch (QueryException $e) {
            $this->command->info('Erreur d’insertion détectée. Processus de seed ignoré pour cet enregistrement.');
        }
    }
}
