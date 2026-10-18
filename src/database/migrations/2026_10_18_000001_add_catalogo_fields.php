<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('genero', 10)->default('unisex');
            $table->string('slug')->nullable();
            $table->boolean('destacado')->default(false);
        });
        Schema::table('marcas', function (Blueprint $table) {
            $table->string('slug')->nullable();
            $table->boolean('destacada')->default(false);
            $table->integer('orden_menu')->nullable();
            $table->string('imagen')->nullable();
        });

        foreach (['productos', 'marcas'] as $tabla) {
            DB::table($tabla)->orderBy('id')->chunkById(100, function ($filas) use ($tabla) {
                foreach ($filas as $fila) {
                    $base = Str::slug($fila->nombre);
                    $slug = $base;
                    $sufijo = 2;
                    while (DB::table($tabla)->where('slug', $slug)->exists()) {
                        $slug = $base.'-'.$sufijo++;
                    }
                    DB::table($tabla)->where('id', $fila->id)->update(['slug' => $slug]);
                }
            });
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('slug')->nullable(false)->change();
                $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['genero', 'slug', 'destacado']);
        });
        Schema::table('marcas', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'destacada', 'orden_menu', 'imagen']);
        });
    }
};
