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
        Schema::create('document_statuses', function (Blueprint $table) {

            $table->id();

            /**
             * Code technique unique
             * Exemple :
             * DRAFT
             * PENDING_REGULATION
             * CLOSED
             */
            $table->string('code')->unique();

            /**
             * Libellé affiché
             */
            $table->string('label');

            /**
             * Description optionnelle
             */
            $table->text('description')->nullable();

            /**
             * Couleur UI
             * Exemple :
             * success
             * warning
             * danger
             */
            $table->string('color')->nullable();

            /**
             * Catégorie du status
             * INITIAL
             * IN_PROGRESS
             * FINAL
             */
            $table->enum('category', [
                'INITIAL',
                'IN_PROGRESS',
                'FINAL'
            ])->default('IN_PROGRESS');

            /**
             * Status final ?
             */
            $table->boolean('is_final')->default(false);

            /**
             * Déclenche reminders ?
             */
            $table->boolean('triggers_reminder')->default(false);

            /**
             * Actif / inactif
             */
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            /**
             * Index utiles
             */
            $table->index('category');
            $table->index('is_final');
            $table->index('triggers_reminder');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_statuses');
    }
};