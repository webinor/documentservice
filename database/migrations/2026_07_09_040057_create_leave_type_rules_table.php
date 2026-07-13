<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_type_rules', function (Blueprint $table) {

            $table->id();

            $table->foreignId('leave_type_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * Nombre maximum de jours autorisés
             * Exemple mariage = 4
             * null = pas de limite
             */
            $table->unsignedInteger('max_days')
                ->nullable();


            /**
             * Nombre de jours payés par l'entreprise
             * Exemple mariage = 4
             */
            $table->unsignedInteger('paid_days')
            ->nullable();


            /**
             * Les jours dépassant paid_days
             * doivent-ils être déduits du solde ?
             */
            $table->boolean('deduct_excess_days')
                ->default(true);


            /**
             * Le congé utilise-t-il le solde annuel ?
             *
             * Congé annuel = true
             * Mariage = false
             */
            $table->boolean('uses_balance')
                ->default(true);


            /**
             * Autorise les demandes fractionnées
             */
            $table->boolean('allow_split')
                ->default(false);


            /**
             * Paramètres supplémentaires évolutifs
             */
            $table->json('settings')
                ->nullable();


            $table->timestamps();

            $table->unique('leave_type_id');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('leave_type_rules');
    }
};