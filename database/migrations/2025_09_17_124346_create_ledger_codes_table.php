<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLedgerCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ledger_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   // Exemple : 601, 707, 215
            $table->string('label')->nullable();            // Libellé du compte
            $table->foreignId('ledger_code_type_id')
                  ->constrained('ledger_code_types')
                  ->cascadeOnDelete();          // Un code appartient à un type

            $table->foreignId('invoice_provider_id')
                  ->constrained('invoice_providers')
                  ->cascadeOnDelete();          // Un code appartient à une facture
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ledger_codes');
    }
}
