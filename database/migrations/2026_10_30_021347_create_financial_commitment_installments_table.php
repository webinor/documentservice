<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialCommitmentInstallmentsTable extends Migration
{
    public function up()
    {
        Schema::create('financial_commitment_installments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('financial_commitment_id');

            $table->unsignedInteger('sequence');

            $table->string('label', 150);
            $table->string('trigger_type', 50);

            $table->unsignedSmallInteger('days_after_trigger')->default(0);

            $table->decimal('percentage', 5, 2);
            $table->decimal('expected_amount', 18, 2);

            $table->date('due_date')->nullable();
            $table->date('triggered_at')->nullable();
            $table->date('paid_at')->nullable();

            /*
             * PENDING, DUE, PARTIALLY_PAID,
             * PAID, CANCELLED.
             */
            $table->string('status', 30)->default('PENDING');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('financial_commitment_id','fci_commitment_fk')
                ->references('id')
                ->on('financial_commitments')
                ->onDelete('cascade');

            $table->unique(
                ['financial_commitment_id', 'sequence'],
                'financial_commitment_installments_sequence_unique'
            );

            $table->index(['status', 'due_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_commitment_installments');
    }
}