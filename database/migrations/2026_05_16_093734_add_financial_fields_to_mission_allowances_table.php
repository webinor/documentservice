<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinancialFieldsToMissionAllowancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mission_allowances', function (Blueprint $table) {

            // ESTIMATED / FINAL
            $table->enum('calculation_stage', [
                'ESTIMATED',
                'FINAL'
            ])->default('ESTIMATED')
              ->after('total');

            // ACTIVE / CANCELLED / ADJUSTED
            $table->string('status')
                  ->default('ACTIVE')
                  ->after('calculation_stage');

                  $table->foreignId('policy_id')->nullable();
                  
                  // snapshot de la policy utilisée
                  $table->json('policy_snapshot')
                  ->nullable()
                  ->after('policy_id');
                  
            // metadata flexible
            $table->json('metadata')
                  ->nullable()
                  ->after('policy_snapshot');

            // validation
            $table->unsignedBigInteger('approved_by')
                  ->nullable()
                  ->after('metadata');

            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');

            // paiement
            $table->timestamp('paid_at')
                  ->nullable()
                  ->after('approved_at');

            // devise
            $table->string('currency', 10)
                  ->default('XAF')
                  ->after('paid_at');

            // taux de conversion
            $table->decimal('exchange_rate', 15, 4)
                  ->default(1)
                  ->after('currency');

            // index utiles
            $table->index('calculation_stage');
            $table->index('status');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mission_allowances', function (Blueprint $table) {

            $table->dropIndex(['calculation_stage']);
            $table->dropIndex(['status']);
            $table->dropIndex(['approved_by']);

            $table->dropColumn([
                'calculation_stage',
                'status',
                'policy_snapshot',
                'metadata',
                'approved_by',
                'approved_at',
                'paid_at',
                'currency',
                'exchange_rate',
            ]);
        });
    }
}