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
        Schema::create('producto_presentacion', function (Blueprint $table) {
    $table->id();
    $table->foreignId('producto_id')->constrained('productos');
    $table->foreignId('tamano_id')->constrained('tamanos');
    $table->decimal('precio', 10, 2);
    $table->integer('stock')->default(0);
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_presentacions');
    }
};
