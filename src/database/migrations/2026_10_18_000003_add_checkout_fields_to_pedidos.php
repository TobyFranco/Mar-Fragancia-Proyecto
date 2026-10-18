<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->change();
            $table->string('numero')->nullable()->unique();
            $table->string('nombre_contacto')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('cedula')->nullable();
            $table->string('email')->nullable();
            $table->enum('metodo_entrega', ['retiro', 'asuncion', 'interior'])->nullable();
            $table->string('ciudad')->nullable();
            $table->string('barrio')->nullable();
            $table->string('direccion')->nullable();
            $table->string('referencia')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('costo_envio', 10, 2)->default(0);
        });
        DB::table('pedidos')->orderBy('id')->chunkById(100, function ($pedidos) {
            foreach ($pedidos as $pedido) {
                DB::table('pedidos')->where('id', $pedido->id)->update([
                    'numero' => 'MF-'.str_pad((string) $pedido->id, 4, '0', STR_PAD_LEFT),
                    'subtotal' => $pedido->total,
                    'costo_envio' => 0,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->dropColumn(['numero', 'nombre_contacto', 'telefono', 'cedula', 'email', 'metodo_entrega', 'ciudad', 'barrio', 'direccion', 'referencia', 'subtotal', 'costo_envio']);
            $table->foreignId('cliente_id')->nullable(false)->change();
        });
    }
};
