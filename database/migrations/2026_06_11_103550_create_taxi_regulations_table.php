<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_regulations', function (Blueprint $table) {

            $table->id();

            $table->foreignId('taxi_paper_id')
                ->constrained()
                ->cascadeOnDelete();

             $table->string('transaction_code')
    ->nullable();

            /**
             * TYPE DE REGULARISATION
             * refund = agent rembourse
             * supplement = entreprise complète
             */
            $table->enum('type', [
                'REFUND',
                'SUPPLEMENT'
            ]);

            $table->dateTime('paid_at')->nullable();


            /**
             * montant de la régularisation
             */
            $table->decimal('amount', 15, 2);

            /**
             * solde avant régularisation (snapshot important)
             */

            /**
             * solde après régularisation (doit tendre vers 0)
             */

            /**
             * statut du paiement
             */
            $table->enum('status', [
                'PENDING',
                'PAID',
                'CANCELLED'
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

            $table->index(['taxi_paper_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_regulations');
    }
};