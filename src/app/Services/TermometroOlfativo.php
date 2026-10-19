<?php

namespace App\Services;

use App\Models\Producto;
use App\Support\Texto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TermometroOlfativo
{
    public const PROMEDIOS_MES = [1 => 35, 2 => 34, 3 => 33, 4 => 29, 5 => 25, 6 => 22, 7 => 22, 8 => 25, 9 => 27, 10 => 30, 11 => 32, 12 => 34];

    public function zonas(): array
    {
        return [
            'frio' => ['clave' => 'frio', 'nombre' => 'Fresco o frío', 'titulo' => 'Con frío: perfumes cálidos y envolventes', 'texto' => 'Con poco calor la piel evapora el perfume más despacio y se proyecta menos. Es el momento de las notas densas y dulces, que en verano serían demasiado.', 'notas_si' => ['vainilla', 'ámbar', 'oud', 'canela', 'tabaco', 'cuero', 'haba tonka'], 'notas_no' => ['cítricos solos', 'notas acuáticas'], 'dosis' => 4],
            'templado' => ['clave' => 'templado', 'nombre' => 'Templado', 'titulo' => 'Templado: el clima más versátil', 'texto' => 'Entre 19° y 26° casi todo funciona. Ideal para florales y aromáticos, que se abren bien sin cansar.', 'notas_si' => ['rosa', 'jazmín', 'iris', 'lavanda', 'almizcle', 'té'], 'notas_no' => ['oud muy intenso'], 'dosis' => 3],
            'calor' => ['clave' => 'calor', 'nombre' => 'Calor', 'titulo' => 'Con calor: frescura que dure', 'texto' => 'El calor acelera la evaporación: el perfume se siente más fuerte al principio y dura menos. Buscá notas frescas con una base que las sostenga.', 'notas_si' => ['bergamota', 'neroli', 'té verde', 'vetiver', 'pimienta rosa'], 'notas_no' => ['vainilla pesada', 'oud', 'canela'], 'dosis' => 2],
            'calor_intenso' => ['clave' => 'calor_intenso', 'nombre' => 'Calor intenso', 'titulo' => 'Más de 32°: liviano, limpio y en poca cantidad', 'texto' => 'Con calor fuerte las notas dulces y pesadas se vuelven empalagosas y molestan a los demás. Lo mejor son los cítricos, verdes y acuáticos, aplicados con moderación.', 'notas_si' => ['limón', 'pomelo', 'menta', 'sal marina', 'notas verdes', 'cardamomo'], 'notas_no' => ['vainilla', 'oud', 'ámbar', 'caramelo'], 'dosis' => 1],
        ];
    }

    public function zona(int $temperatura): array
    {
        $clave = match (true) {
            $temperatura <= 18 => 'frio',
            $temperatura <= 26 => 'templado',
            $temperatura <= 32 => 'calor',
            default => 'calor_intenso',
        };

        return $this->zonas()[$clave];
    }

    public function dosis(int $temperatura, bool $noche, bool $humedo): int
    {
        $dosis = $this->zona($temperatura)['dosis'];
        if ($noche) {
            $dosis = min(4, $dosis + 1);
        }

        return $humedo ? max(1, $dosis - 1) : $dosis;
    }

    public function recomendaciones(int $temperatura, int $limite = 3): Collection
    {
        $zona = $this->zona($temperatura);
        $buenas = collect($zona['notas_si'])->map(fn (string $nota) => Texto::normalizar($nota));
        $malas = collect($zona['notas_no'])->map(fn (string $nota) => Texto::normalizar($nota));

        return Producto::visibles()->with(['notas', 'marca', 'presentaciones.tamano'])
            ->whereHas('presentaciones', fn (Builder $p) => $p->where('activo', true)->where('stock', '>', 0)->whereHas('tamano', fn (Builder $t) => $t->whereIn('nombre', ['5 ml', '10 ml'])))->get()
            ->filter(function (Producto $p) use ($buenas, $malas): bool {
                $notas = $p->notas->pluck('nombre')->map(fn (string $nota) => Texto::normalizar($nota));

                return $notas->intersect($buenas)->isNotEmpty() && $notas->intersect($malas)->isEmpty();
            })
            ->sort(function (Producto $a, Producto $b) use ($buenas): int {
                $coincidencias = fn (Producto $p) => $p->notas->pluck('nombre')->map(fn (string $n) => Texto::normalizar($n))->intersect($buenas)->count();

                return ($coincidencias($b) <=> $coincidencias($a)) ?: strcmp($a->nombre, $b->nombre);
            })->take($limite)->values();
    }
}
