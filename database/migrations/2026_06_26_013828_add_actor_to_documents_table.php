<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {

             $table->string('actor_type')
                ->nullable()
                ->comment("Type de Personne concernee par le document ( employee ,supplier...,  )")
                ->after('created_by');


            $table->unsignedBigInteger('actor_id')
                ->comment("Personne concernee par le document ( employee_id, supplier_id )")
                ->after('actor_type');

       

            $table->index('actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['actor_id']);
            $table->dropColumn(['actor_type' , 'actor_id']);
        });
    }
};