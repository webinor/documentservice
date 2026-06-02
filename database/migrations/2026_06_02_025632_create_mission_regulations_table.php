<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_regulations', function (Blueprint $table) {

            $table->id();

            $table->foreignId('mission_id')
                ->constrained()
                ->cascadeOnDelete();

             $table->unsignedInteger('transaction_id')
    ->nullable();

            /**
             * TYPE DE REGULARISATION
             * refund = agent rembourse
             * supplement = entreprise complète
             */
            $table->enum('type', [
                'refund',
                'supplement'
            ]);

            /**
             * montant de la régularisation
             */
            $table->decimal('amount', 15, 2);

            /**
             * solde avant régularisation (snapshot important)
             */
            $table->decimal('balance_before', 15, 2)->nullable();

            /**
             * solde après régularisation (doit tendre vers 0)
             */
            $table->decimal('balance_after', 15, 2)->default(0);

            /**
             * statut du paiement
             */
            $table->enum('status', [
                'pending',
                'processed',
                'cancelled'
            ])->default('pending');

            /**
             * moyen de régularisation (cash, bank, deduction, etc.)
             */
            $table->string('payment_method')->nullable();

            /**
             * référence bancaire ou comptable
             */
            $table->string('reference')->nullable();

            /**
             * commentaire finance
             */
            $table->text('comment')->nullable();

            /**
             * audit
             */
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['mission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_regulations');
    }
};