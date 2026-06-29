<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | LIEN METIER (DOCUMENT / TAXI / FEE / MISSION...)
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('transactable_id');
            $table->string('transactable_type');

            /*
            |--------------------------------------------------------------------------
            | IDENTIFIANTS DE TRANSACTION
            |--------------------------------------------------------------------------
            */
            $table->string('transaction_code')->nullable();
            $table->string('transaction_type_code')->nullable();

            /*
            |--------------------------------------------------------------------------
            | TYPE FINANCIER (cycle de la transaction)
            |--------------------------------------------------------------------------
            | ADVANCE     = avance versée à l'agent / employé
            | SETTLEMENT  = régularisation d’un cycle (fin de mission, solde)
            | ONE_SHOT    = paiement direct sans avance ni régularisation
            |--------------------------------------------------------------------------
            */
            $table->enum('type', [
                'ADVANCE',
                'SETTLEMENT',
                'ONE_SHOT',
            ]);

                        /*
            |--------------------------------------------------------------------------
            | AJUSTEMENT FINANCIER (résultat du SETTLEMENT)
            |--------------------------------------------------------------------------
            | NONE        = pas d'ajustement
            | REFUND      = trop perçu → remboursement
            | SUPPLEMENT  = manque → complément à payer
            |--------------------------------------------------------------------------
            */
            $table->enum('adjustment_type', [
                'NONE',
                'REFUND',
                'SUPPLEMENT',
            ])->default('NONE');

            /*
            |--------------------------------------------------------------------------
            | DIRECTION COMPTABLE
            |--------------------------------------------------------------------------
            | IN  = entrée (argent reçu)
            | OUT = sortie (argent payé)
            |--------------------------------------------------------------------------
            */
            $table->enum('direction', [
                'IN',
                'OUT'
            ]);

            /*
            |--------------------------------------------------------------------------
            | STATUT PAIEMENT
            |--------------------------------------------------------------------------
            */
            $table->enum('status', [
                'PENDING',
                'PROCESSING',
                'PAID',
                'FAILED',
                'CANCELLED'
            ])->default('PENDING');

            /*
            |--------------------------------------------------------------------------
            | MONTANT
            |--------------------------------------------------------------------------
            */
            $table->decimal('amount', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | PAIEMENT
            |--------------------------------------------------------------------------
            */
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();

            /*
            |--------------------------------------------------------------------------
            | DATES
            |--------------------------------------------------------------------------
            */
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | AUDIT
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('comment')->nullable();

            /*
            |--------------------------------------------------------------------------
            | EXTENSION (REMPLACE rules)
            |--------------------------------------------------------------------------
            */
            $table->json('metadata')->nullable();

            /*
            |--------------------------------------------------------------------------
            | ACTIVATION
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            */
            $table->index(['transactable_id', 'transactable_type']);
            $table->index(['status']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};