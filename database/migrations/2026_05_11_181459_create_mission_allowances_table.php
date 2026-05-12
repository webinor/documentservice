<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mission_allowances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mission_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('allowance_type_id')
                ->constrained('allowance_types')
                ->onDelete('restrict');

            $table->integer('quantity')->default(1);
            $table->decimal('unit_amount', 15, 2);
            $table->decimal('total', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_allowances');
    }
};