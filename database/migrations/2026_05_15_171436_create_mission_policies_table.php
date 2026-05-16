<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_policies', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Type d'allocation (lié à allowance_types)
            |--------------------------------------------------------------------------
            */

            $table->foreignId('allowance_type_id')
                ->constrained('allowance_types')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Scope d'application
            |--------------------------------------------------------------------------
            */

            $table->enum('scope', [
                'LOCAL',          // interne ville / site
                'NATIONAL',       // dans le pays
                'INTERNATIONAL'   // hors pays
            ]);

            $table->string('country_code')->nullable();
            // utile si scope = NATIONAL ou INTERNATIONAL spécifique

            /*
            |--------------------------------------------------------------------------
            | Catégorie RH / Poste (filtrage métier)
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('employee_category_id')->nullable();

            $table->unsignedSmallInteger('position_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Règle de calcul
            |--------------------------------------------------------------------------
            */

            $table->enum('calculation_type', [
                'FIXED',        // montant fixe
                'PER_DAY',      // par jour
                'PERCENTAGE'    // % (ex: prime sur budget mission)
            ]);

            $table->decimal('amount', 15, 2)->nullable();
            // utilisé pour FIXED ou PER_DAY

            $table->decimal('percentage', 5, 2)->nullable();
            // utilisé si PERCENTAGE

            /*
            |--------------------------------------------------------------------------
            | Logique temporelle (ton cas important)
            |--------------------------------------------------------------------------
            */

            $table->integer('start_day')->nullable();
            // ex: commencer à payer à partir du jour 4

            $table->integer('end_day')->nullable();
            // ex: arrêter après jour 10

            $table->boolean('apply_after_threshold')->default(false);
            // permet de clarifier la règle

            /*
            |--------------------------------------------------------------------------
            | Conditions avancées (future-proof)
            |--------------------------------------------------------------------------
            */

            $table->json('conditions')->nullable();
            /*
            ex :
            {
                "min_budget": 1000000,
                "requires_approval": "DG",
                "mission_type": "AUDIT"
            }
            */

            /*
            |--------------------------------------------------------------------------
            | Statut
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_policies');
    }
};