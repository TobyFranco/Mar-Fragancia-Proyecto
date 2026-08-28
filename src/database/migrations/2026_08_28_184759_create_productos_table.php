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
        Schema::create('productos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('marca_id')->constrained('marcas');
    $table->foreignId('categoria_id')->constrained('categorias');
    $table->string('nombre', 150);
    $table->text('descripcion')->nullable();
    $table->string('imagen', 255)->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
