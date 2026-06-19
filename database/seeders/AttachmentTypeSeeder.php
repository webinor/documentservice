<?php

namespace Database\Seeders;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Misc\AttachmentType;
use App\Models\AttachmentTypeCategory;

class AttachmentTypeSeeder extends Seeder
{
    public function run()
    {
        // 🔥 active / désactive le mode test ici
        $testMode = env('SEEDER_TEST_MODE', false);

        if ($testMode) {
            $this->console("=== SEEDER ATTACHMENT TYPE : MODE TEST ACTIVÉ ===");
        }

        DB::beginTransaction();

        try {

            /**
             * ---------------------------------------------------------
             * Categories
             * ---------------------------------------------------------
             */

            $engagementCat = $this->createCategory('Engagment', $testMode);
            $paymentCat    = $this->createCategory('Payment', $testMode);
            $missionCat    = $this->createCategory('Mission', $testMode);

            /**
             * ---------------------------------------------------------
             * Types Engagement
             * ---------------------------------------------------------
             */
            $engagementTypes = [
                'Facture originale' => false,
                'Contrat' => false,
                'Bon de regularisation' => false,
                'Demande achat' => false,
                'Bon de commande' => false,
                'Bon de livraison' => false,
                'Devis' => false,
                'Attestation' => false,
                'Preuve de paiement' => false,
                'Timesheet' => false,
                'Note avoir' => false,
                'Autre' => false,
            ];

            $this->createTypes($engagementTypes, $engagementCat->id, 'Engagement', $testMode);

            /**
             * ---------------------------------------------------------
             * Types Paiement
             * ---------------------------------------------------------
             */
            $paymentTypes = [
                'Preuve de paiement' => true,
                'Attestation de reglement' => false,
                'Numero de piece Facture' => true,
            ];

            $this->createTypes($paymentTypes, $paymentCat->id, 'Paiement', $testMode);

            /**
             * ---------------------------------------------------------
             * Types Mission
             * ---------------------------------------------------------
             */
            $missionTypes = [
                'Lettre de mission' => false,
                'Ordre de mission' => false,
                'Feuille de Mission' => false,
                'Rapport de Mission' => false,
                'Fiche à regulariser' => false,
            ];

            $this->createTypes($missionTypes, $missionCat->id, 'Mission', $testMode);

            if (!$testMode) {
                DB::commit();
                $this->console("Seeder exécuté avec succès (commit)");
            } else {
                DB::rollBack();
                $this->console("Seeder terminé en MODE TEST (rollback effectué, aucune écriture)");
            }

        } catch (Throwable $e) {

            DB::rollBack();
            Log::error("Erreur Seeder AttachmentType: " . $e->getMessage(), [
                'exception' => $e
            ]);

            throw $e;
        }
    }

    /**
     * Création catégorie avec log
     */
    private function createCategory(string $name, bool $testMode)
    {
        $slug = Str::slug($name);

        $existing = AttachmentTypeCategory::where('slug', $slug)->first();

        if ($existing) {
            $this->console("[CATEGORY EXISTE] {$name}");
            return $existing;
        }

        if ($testMode) {
            $this->console("[CATEGORY À CRÉER] {$name}");
            return (object)[
                'id' => null
            ];
        }

        $this->console("[CATEGORY CRÉÉE] {$name}");

        return AttachmentTypeCategory::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
    }

    /**
     * Création types avec log
     */
    private function createTypes(array $types, $categoryId, string $group, bool $testMode)
    {
        foreach ($types as $type => $required) {

            $slug = Str::slug($type);

            $existing = AttachmentType::where('slug', $slug)->first();

            if ($existing) {
                $this->console("[{$group} EXISTE] {$type}");
                continue;
            }

            if ($testMode) {
                $this->console("[{$group} À CRÉER] {$type}");
                continue;
            }

            $this->console("[{$group} CRÉÉ] {$type}");

            AttachmentType::firstOrCreate(
                ['slug' => $slug],
                [
                    'attachment_type_category_id' => $categoryId,
                    'name' => $type,
                    'attachment_number_required' => $required,
                ]
            );
        }
    }

    private function console(string $message)
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        } else {
            $this->console($message);
        }
    }
}