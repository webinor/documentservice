<?php

namespace Database\Seeders;

use App\Models\LedgerCodeType;
use Illuminate\Database\Seeder;

class LedgerCodeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
          // Exemple de types
    $types = [
            'Charges' => 'Comptes liés aux charges de l’entreprise',
            'Produits' => 'Comptes liés aux produits (revenus)',
            'Immobilisations' => 'Comptes liés aux immobilisations',
        ];

        foreach ($types as $name => $description) {
            $type = LedgerCodeType::create([
                'name' => $name,
                'description' => $description,
            ]);
        }
    }
}
