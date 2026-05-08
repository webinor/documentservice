<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMissionExpensesTableNullableFields extends Migration
{
    public function up()
    {
        Schema::table('mission_expenses', function (Blueprint $table) {

            $table->foreignId('expense_category_id')
                ->nullable()
                ->change();

            $table->decimal('amount', 12, 2)
                ->nullable()
                ->change();

        });
    }

    public function down()
    {
        Schema::table('mission_expenses', function (Blueprint $table) {

            $table->foreignId('expense_category_id')
                ->nullable(false)
                ->change();

            $table->decimal('amount', 12, 2)
                ->nullable(false)
                ->change();

        });
    }
}