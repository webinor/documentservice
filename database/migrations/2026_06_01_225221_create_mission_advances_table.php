<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mission_advances', function (Blueprint $table) {

            $table->id();

            $table->foreignId('mission_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('transaction_id')
    ->nullable();

            $table->decimal('amount', 15, 2);

            $table->date('payment_date');

            $table->string('reference')->nullable();

            $table->text('comment')->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'cancelled'
            ])->default('pending');

            $table->unsignedSmallInteger('created_by') ->nullable();

            $table->unsignedSmallInteger('validated_by') ->nullable();

            $table->timestamp('validated_at')
                ->nullable();

            $table->timestamps();

            $table->index('mission_id');
            $table->index('payment_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_advances');
    }
};