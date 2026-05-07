<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissionsTable extends Migration
{
    public function up()
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();

            // 👤 acteur principal (interne OU externe)
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_type')->nullable(); 
            // INTERNAL | EXTERNAL

            // 📄 infos mission
            $table->string('title');
            $table->text('description')->nullable();

            // 📍 localisation
            $table->string('destination')->nullable();

            // 📅 dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('return_date')->nullable();

            // 💰 budget
            $table->decimal('estimated_budget', 12, 2)->nullable();
            $table->decimal('advance_amount', 12, 2)->nullable();

            // ⚙️ statut workflow
            $table->string('status')->default('DRAFT');
            // DRAFT | IN_VALIDATION | VALIDATED | IN_PROGRESS | RETURNED | CLOSED | REJECTED

            // 🧠 type mission
            $table->enum('mission_type', ['SHORT', 'LONG'])->default('SHORT');

            // 🧾 meta workflow
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('missions');
    }
}