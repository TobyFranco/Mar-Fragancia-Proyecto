<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class GenerarDiccionario extends Command
{
    protected $signature = 'docs:diccionario {--output= : Archivo Markdown de salida (por defecto docs/datos/diccionario-de-datos.md)}';

    protected $description = 'Documenta columnas, índices y claves del esquema MySQL real sin modificarlo';

    private const INFRAESTRUCTURA = ['cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens', 'migrations', 'personal_access_tokens'];

    public function handle(): int
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->error('El diccionario requiere MySQL: SQLite no representa sus tipos exactos.');

            return self::FAILURE;
        }

        $descripciones = require base_path('../docs/datos/descripciones.php');
        $tablas = collect(Schema::getTables())->pluck('name')->sort()->values();
        $version = DB::selectOne('SELECT VERSION() AS version')->version;
        $texto = "# Diccionario de datos\n\nFuente: MySQL {$version}, base `".Schema::getConnection()->getDatabaseName()."`. Generado por `php artisan docs:diccionario` con Schema::getTables/getColumns/getIndexes/getForeignKeys. No consulta datos de clientes ni modifica el esquema.\n\n";
        $texto .= "Tipos y nulabilidad son los del servidor; no se sustituyen por casts de Eloquent. `NULL / no declarado` significa que la introspección devuelve default nulo: no diferencia la omisión de DEFAULT del DEFAULT NULL explícito. Los valores no nulos reproducen el texto devuelto por MySQL. AUTO_INCREMENT se indica en la descripción. PK/UQ compuestas se identifican con su conjunto de columnas, no como unicidad individual.\n\n";
        foreach (['Tablas de negocio' => false, 'Tablas de infraestructura' => true] as $seccion => $infra) {
            $texto .= "## {$seccion}\n\n";
            foreach ($tablas as $tabla) {
                if (in_array($tabla, self::INFRAESTRUCTURA, true) !== $infra) {
                    continue;
                }
                $columnas = Schema::getColumns($tabla);
                $indices = Schema::getIndexes($tabla);
                $fks = Schema::getForeignKeys($tabla);
                if (empty($descripciones[$tabla]['_tabla'])) {
                    $this->error("Falta descripción de tabla: {$tabla}");

                    return self::FAILURE;
                }
                $texto .= "### {$tabla}\n\n".$descripciones[$tabla]['_tabla']."\n\n| Campo | Tipo MySQL | Nulo | Default | Clave | Descripción |\n|---|---|---|---|---|---|\n";
                foreach ($columnas as $columna) {
                    $nombre = $columna['name'];
                    if (empty($descripciones[$tabla][$nombre])) {
                        $this->error("Falta descripción: {$tabla}.{$nombre}");

                        return self::FAILURE;
                    }
                    $claves = [];
                    foreach ($indices as $indice) {
                        if (in_array($nombre, $indice['columns'], true) && ($indice['primary'] || $indice['unique'])) {
                            $claves[] = ($indice['primary'] ? 'PK' : 'UQ').' ('.implode(', ', $indice['columns']).')';
                        }
                    }
                    foreach ($fks as $fk) {
                        $posicion = array_search($nombre, $fk['columns'], true);
                        if ($posicion !== false) {
                            $claves[] = 'FK → '.$fk['foreign_table'].'.'.$fk['foreign_columns'][$posicion];
                        }
                    }
                    $default = $columna['default'] === null ? 'NULL / no declarado' : (string) $columna['default'];
                    $detalle = $descripciones[$tabla][$nombre].($columna['auto_increment'] ? ' Autoincremental.' : '');
                    $texto .= $this->fila([$nombre, $columna['type'], $columna['nullable'] ? 'Sí' : 'No', $default, implode('; ', $claves) ?: '—', $detalle]);
                }
                $texto .= "\n**Índices** (nombres normalizados por Laravel)\n\n| Nombre | Columnas | Único | Tipo | Utilidad |\n|---|---|---|---|---|\n";
                foreach ($indices as $indice) {
                    $uso = $indice['primary'] ? 'Identifica filas por la clave primaria.' : ($indice['unique'] ? 'Impide repetir la combinación indexada; MySQL admite varios NULL si las columnas lo permiten.' : 'Apoya búsquedas y uniones por las columnas indicadas; su uso efectivo depende de la consulta y del optimizador.');
                    $texto .= $this->fila([$indice['name'], implode(', ', $indice['columns']), $indice['unique'] ? 'Sí' : 'No', $indice['type'], $uso]);
                }
                $texto .= "\n**Claves foráneas**\n\n";
                if ($fks === []) {
                    $texto .= "No hay FK SQL. Las relaciones lógicas no se inventan como restricciones de la base.\n";
                } else {
                    $texto .= "| Nombre | Columnas | Referencia | ON DELETE | ON UPDATE |\n|---|---|---|---|---|\n";
                    foreach ($fks as $fk) {
                        $texto .= $this->fila([$fk['name'], implode(', ', $fk['columns']), $fk['foreign_schema'].'.'.$fk['foreign_table'].' ('.implode(', ', $fk['foreign_columns']).')', strtoupper($fk['on_delete']), strtoupper($fk['on_update'])]);
                    }
                }
                $texto .= "\n**Reglas y semántica de aplicación**\n\n";
                foreach ($descripciones[$tabla]['_reglas'] ?? ['No se agregan reglas de negocio específicas a las restricciones y al comportamiento del framework documentados arriba.'] as $regla) {
                    $texto .= '- '.$regla."\n";
                }
                $texto .= "\n";
            }
        }
        $ausentes = array_diff(self::INFRAESTRUCTURA, $tablas->all());
        $texto .= 'Tablas de infraestructura contempladas pero ausentes del esquema inspeccionado: '.($ausentes ? implode(', ', $ausentes) : 'ninguna').". No se generan filas ni relaciones para tablas inexistentes.\n";
        $salida = $this->option('output') ?: base_path('../docs/datos/diccionario-de-datos.md');
        File::ensureDirectoryExists(dirname($salida));
        File::replace($salida, $texto, 0644);
        $this->info('Diccionario generado desde MySQL: '.$tablas->count().' tablas.');

        return self::SUCCESS;
    }

    private function fila(array $valores): string
    {
        return '| '.implode(' | ', array_map(fn ($valor) => str_replace(["\r", "\n", '|'], ['', '<br>', '\\|'], (string) $valor), $valores))." |\n";
    }
}
