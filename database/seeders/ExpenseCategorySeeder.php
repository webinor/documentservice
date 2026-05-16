<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [

            /**
             * ============================================
             * TRANSPORTS
             * ============================================
             */

            [
                'code' => 'TRANSPORT',
                'name' => 'Transport interurbain',
            ],

            [
                'code' => 'TAXI',
                'name' => 'Taxi',
            ],

            [
                'code' => 'LOCAL_TAXI',
                'name' => 'Taxi local',
            ],

            [
                'code' => 'TAXI_SUPPLEMENT',
                'name' => 'Complément Taxi',
            ],

            [
                'code' => 'FUEL',
                'name' => 'Carburant',
            ],

            [
                'code' => 'TOLL',
                'name' => 'Péage routier',
            ],

            [
                'code' => 'PARKING',
                'name' => 'Parking',
            ],

            [
                'code' => 'VEHICLE_RENTAL',
                'name' => 'Location véhicule',
            ],

            /**
             * ============================================
             * HEBERGEMENT
             * ============================================
             */

            [
                'code' => 'HEBERGEMENT',
                'name' => 'Hébergement',
            ],

            [
                'code' => 'HOTEL',
                'name' => 'Hôtel',
            ],

            /**
             * ============================================
             * REPAS / RATIONS
             * ============================================
             */

            [
                'code' => 'REPAS',
                'name' => 'Repas',
            ],

            [
                'code' => 'BREAKFAST',
                'name' => 'Petit déjeuner',
            ],

            [
                'code' => 'LUNCH',
                'name' => 'Déjeuner',
            ],

            [
                'code' => 'DINNER',
                'name' => 'Dîner',
            ],

            [
                'code' => 'RATION',
                'name' => 'Ration',
            ],

            /**
             * ============================================
             * COMMUNICATION
             * ============================================
             */

            [
                'code' => 'COMMUNICATION',
                'name' => 'Communication',
            ],

            [
                'code' => 'INTERNET',
                'name' => 'Internet / Data',
            ],

            /**
             * ============================================
             * INTERNATIONAL
             * ============================================
             */

            [
                'code' => 'VISA',
                'name' => 'Visa',
            ],

            [
                'code' => 'TRAVEL_INSURANCE',
                'name' => 'Assurance voyage',
            ],

            [
                'code' => 'EXCHANGE_FEES',
                'name' => 'Frais de change',
            ],

            /**
             * ============================================
             * FRAIS SPECIAUX
             * ============================================
             */

            [
                'code' => 'PUBLIC_RELATIONS',
                'name' => 'Relations publiques',
            ],

            [
                'code' => 'MISSION_BONUS',
                'name' => 'Prime de mission',
            ],

            /**
             * ============================================
             * AUTRES
             * ============================================
             */

            [
                'code' => 'LUGGAGE',
                'name' => 'Frais de bagages',
            ],

            [
                'code' => 'BANK_FEES',
                'name' => 'Frais bancaires',
            ],

            [
                'code' => 'DIVERS',
                'name' => 'Divers',
            ],

        ];

        foreach ($categories as $category) {

            DB::table('expense_categories')->updateOrInsert(

                [
                    'code' => $category['code']
                ],

                [
                    'name' => $category['name'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}