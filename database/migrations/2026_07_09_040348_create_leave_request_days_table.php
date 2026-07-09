<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('leave_request_days', function(Blueprint $table){


            $table->id();


            $table->foreignId('leave_request_id')
                ->constrained()
                ->cascadeOnDelete();


            /**
             * Date concernée
             */
            $table->date('date');


            /**
             * Type appliqué ce jour
             *
             * MARRIAGE
             * ANNUAL
             * SICK
             */
            $table->foreignId('leave_type_id')
                ->constrained();


            /**
             * Catégorie de couverture
             */
            $table->enum('coverage_type',[
                'PAID',
                'BALANCE',
                'UNPAID'
            ]);


            /**
             * Est-ce que ce jour
             * consomme le solde ?
             */
            $table->boolean('deducts_balance')
                ->default(false);


            /**
             * Nombre consommé
             */
            $table->decimal(
                'deduct_days',
                5,
                2
            )
            ->default(0);


            /**
             * Weekend / jour férié
             */
            $table->boolean('is_non_working_day')
                ->default(false);


            $table->string('comment')
                ->nullable();


            $table->timestamps();


            $table->unique([
                'leave_request_id',
                'date'
            ]);

        });

    }


    public function down(): void
    {
        Schema::dropIfExists('leave_request_days');
    }

};