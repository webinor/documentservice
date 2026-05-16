<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinancialFieldsToMissionExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mission_expenses', function (Blueprint $table) {

            // Total calculé
            $table->decimal('total', 15, 2)
                  ->default(0)
                  ->after('amount');

            // Devise
            $table->string('currency', 10)
                  ->default('XAF')
                  ->after('total');

            // Justification / commentaire
            $table->text('justification')
                  ->nullable()
                  ->after('currency');

            // Statut validation
            $table->string('status')
                  ->default('PENDING')
                  ->after('justification');

            // Validateur RH / Finance
            $table->unsignedBigInteger('approved_by')
                  ->nullable()
                  ->after('status');

            // Date validation
            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');

            // Index utiles
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
        Schema::table('mission_expenses', function (Blueprint $table) {

            $table->dropIndex(['status']);
            $table->dropIndex(['approved_by']);

            $table->dropColumn([
                'total',
                'currency',
                'justification',
                'status',
                'approved_by',
                'approved_at',
            ]);
        });
    }
}