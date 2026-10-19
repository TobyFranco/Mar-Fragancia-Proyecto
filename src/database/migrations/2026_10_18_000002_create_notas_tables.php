<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
        });
        Schema::create('nota_producto', function (Blueprint $table) {
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('nota_id')->constrained('notas')->cascadeOnDelete();
            $table->primary(['producto_id', 'nota_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_producto');
        Schema::dropIfExists('notas');
    }
};
