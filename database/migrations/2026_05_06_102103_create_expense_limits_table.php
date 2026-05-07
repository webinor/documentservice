<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpenseLimitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expense_limits', function (Blueprint $table) {
             $table->id();

            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->onDelete('cascade');

            $table->string('mission_type'); 
            // SHORT | LONG

            $table->decimal('amount', 12, 2);

            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();

            $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('expense_limits');
    }
}
