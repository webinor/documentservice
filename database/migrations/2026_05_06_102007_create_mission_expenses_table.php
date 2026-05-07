<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissionExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mission_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mission_id')
                ->constrained('missions')
                ->onDelete('cascade');

            $table->foreignId('expense_category_id')
                ->constrained('expense_categories');

            $table->decimal('amount', 12, 2);

            $table->date('expense_date')->nullable();

            $table->string('description')->nullable();

            // justificatif (PDF/image)
            $table->string('receipt_path')->nullable();

            // audit
            $table->boolean('is_validated')->default(false);

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
        Schema::dropIfExists('mission_exprenses');
    }
}
