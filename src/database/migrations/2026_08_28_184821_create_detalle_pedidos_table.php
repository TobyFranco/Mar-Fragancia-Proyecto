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
        Schema::create('detalle_pedidos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pedido_id')->constrained('pedidos');
    $table->foreignId('presentacion_id')->constrained('producto_presentacion');
    $table->integer('cantidad');
    $table->decimal('precio_unitario', 10, 2);
    $table->decimal('subtotal', 10, 2);
    $table->timestamp('created_at')->useCurrent();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};
