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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
    $table->id();
    $table->foreignId('presentacion_id')->constrained('producto_presentacion');
    $table->string('tipo', 20); // 'entrada' o 'salida'
    $table->integer('cantidad');
    $table->dateTime('fecha');
    $table->string('motivo', 255)->nullable();
    $table->timestamp('created_at')->useCurrent();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventarios');
    }
};
