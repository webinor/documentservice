<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_transactions', function (Blueprint $table) {


            $table->id();


            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('leave_balance_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('leave_request_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();


            /**
             * CREDIT :
             * - acquisition annuelle
             * - report année précédente
             *
             * DEBIT :
             * - consommation
             * - correction négative
             */
            $table->enum('type', [
                'CREDIT',
                'DEBIT',
                'ADJUSTMENT'
            ]);


            /**
             * Nombre de jours mouvementés
             */
            $table->decimal(
                'days',
                5,
                2
            );


            /**
             * Solde avant mouvement
             */
            $table->decimal(
                'balance_before',
                5,
                2
            );


            /**
             * Solde après mouvement
             */
            $table->decimal(
                'balance_after',
                5,
                2
            );


            /**
             * Origine :
             * ANNUAL_ACCRUAL
             * LEAVE_REQUEST
             * ADMIN_CORRECTION
             */
            $table->string('source')
                ->nullable();


            $table->text('description')
                ->nullable();


            /**
             * Qui a généré le mouvement
             */
            $table->unsignedBigInteger('created_by')
                ->nullable();


            $table->timestamps();


            /**
             * Index pour historique employé
             */
            $table->index([
                'employee_id',
                'created_at'
            ]);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('leave_transactions');
    }
};