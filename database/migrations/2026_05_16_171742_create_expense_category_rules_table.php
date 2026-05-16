<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_category_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('expense_category_id')
                ->constrained()
                ->onDelete('cascade');

            // TYPE DE RÈGLE
            $table->string('rule_type'); 
            // TIME_WINDOW | FIXED | DAILY | NONE

            // POUR RÈGLES TEMPORELLES
            $table->time('start_time')->nullable(); // ex: 06:00
            $table->time('end_time')->nullable();   // ex: 10:00

            // QUANTITÉ GÉNÉRÉE
            $table->decimal('quantity', 10, 2)->default(1);

            // PRIORITÉ (si plusieurs règles)
            $table->integer('priority')->default(0);

            // ACTIVATION
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_category_rules');
    }
};