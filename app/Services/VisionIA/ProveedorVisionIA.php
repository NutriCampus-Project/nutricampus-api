<?php

namespace App\Services\VisionIA;

/**
 * Adaptador principal de Vision IA (patron Adapter - Figura 7).
 * Valida formato/tamano (RF08, RNF02), delega en ClienteApiExterna y calcula macronutrientes.
 */
class ProveedorVisionIA implements ServicioVisionIA
{
    private const TAMANO_MAXIMO_KB = 5120;

    /**
     * Mapa de sinonimos devueltos frecuentemente por APIs de vision hacia claves canonicas.
     *
     * @var array<string, string>
     */
    private const SINONIMOS = [
        'trucha' => 'trucha_frita',
        'arroz' => 'arroz_blanco',
        'papa' => 'papa_hervida',
        'papa_sancochada' => 'papa_hervida',
        'ensalada_fresca' => 'ensalada',
        'quinua' => 'pesque_quinua',
        'sopa_de_quinua' => 'sopa_quinua',
        'chaquepa_de_trigo' => 'chaquepa',
        'tallarines' => 'tallarines_rojos',
        'frijol' => 'frijoles',
        'frejoles' => 'frijoles',
        'habas' => 'habas_sancochadas',
        'chuno' => 'chuno_cocido',
        'mate_de_coca' => 'mate_coca',
        'pollo' => 'pollo_horno',
        'chaufa' => 'arroz_chaufa',
    ];

    /**
     * Densidad nutricional por gramo calibrada para platos de la UNA Puno.
     *
     * @var array<string, array{calorias: float, proteinas: float, carbohidratos: float}>
     */
    private array $tablaNutricional = [
        'trucha_frita' => ['calorias' => 1.90, 'proteinas' => 0.21, 'carbohidratos' => 0.00],
        'arroz_blanco' => ['calorias' => 1.30, 'proteinas' => 0.026, 'carbohidratos' => 0.28],
        'papa_hervida' => ['calorias' => 0.87, 'proteinas' => 0.02, 'carbohidratos' => 0.20],
        'ensalada' => ['calorias' => 0.25, 'proteinas' => 0.01, 'carbohidratos' => 0.05],
        'pesque_quinua' => ['calorias' => 1.45, 'proteinas' => 0.055, 'carbohidratos' => 0.24],
        'guiso_res' => ['calorias' => 1.85, 'proteinas' => 0.19, 'carbohidratos' => 0.04],
        'pollo_horno' => ['calorias' => 1.65, 'proteinas' => 0.25, 'carbohidratos' => 0.00],
        'lentejas' => ['calorias' => 1.16, 'proteinas' => 0.09, 'carbohidratos' => 0.20],
        'chuno_cocido' => ['calorias' => 1.36, 'proteinas' => 0.019, 'carbohidratos' => 0.32],
        'sopa_quinua' => ['calorias' => 0.72, 'proteinas' => 0.032, 'carbohidratos' => 0.12],
        'chaquepa' => ['calorias' => 0.85, 'proteinas' => 0.035, 'carbohidratos' => 0.15],
        'pollo_frito' => ['calorias' => 2.45, 'proteinas' => 0.22, 'carbohidratos' => 0.08],
        'tallarines_rojos' => ['calorias' => 1.55, 'proteinas' => 0.06, 'carbohidratos' => 0.25],
        'frijoles' => ['calorias' => 1.27, 'proteinas' => 0.087, 'carbohidratos' => 0.228],
        'habas_sancochadas' => ['calorias' => 1.10, 'proteinas' => 0.076, 'carbohidratos' => 0.197],
        'arroz_chaufa' => ['calorias' => 1.68, 'proteinas' => 0.058, 'carbohidratos' => 0.24],
        'mate_coca' => ['calorias' => 0.02, 'proteinas' => 0.001, 'carbohidratos' => 0.004],
    ];

    public function __construct(
        private readonly ClienteApiExterna $clienteApi
    ) {}

    public function analizarImagen(
        string $archivo,
        int $tamanoKb,
        array $porcionesGramos = [],
        ?string $rutaImagen = null
    ): ResultadoVision {
        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return new ResultadoVision(
                estado: 'ERROR',
                mensaje: 'Formato de imagen no permitido. Use JPG, JPEG o PNG',
                archivo: $archivo,
                tamanoKb: $tamanoKb,
                proveedor: 'proveedor-vision-ia',
            );
        }

        if ($tamanoKb <= 0 || $tamanoKb > self::TAMANO_MAXIMO_KB) {
            return new ResultadoVision(
                estado: 'ERROR',
                mensaje: 'La imagen supera el tamano maximo de 5 MB (5120 KB)',
                archivo: $archivo,
                tamanoKb: $tamanoKb,
                proveedor: 'proveedor-vision-ia',
            );
        }

        $respuestaCruda = $this->clienteApi->enviarSolicitud($archivo, $porcionesGramos, $rutaImagen);

        return $this->traducirRespuesta($respuestaCruda, $archivo, $tamanoKb);
    }

    /**
     * Traduce la respuesta cruda del cliente externo al formato unificado ResultadoVision.
     *
     * @param  array{proveedor?: string, confianza?: float, alimentos?: array<string, float|int>}  $respuestaCruda
     */
    private function traducirRespuesta(array $respuestaCruda, string $archivo, int $tamanoKb): ResultadoVision
    {
        $proveedor = (string) ($respuestaCruda['proveedor'] ?? 'motor-vision-unap');
        $confianza = (float) ($respuestaCruda['confianza'] ?? 0.0);
        $porcionesCrudas = $respuestaCruda['alimentos'] ?? [];
        $esApiExterna = in_array($proveedor, ['groq-llama-4-scout', 'gemini-2.5-flash-lite'], true);

        $alimentosDetectados = [];
        $caloriasTotal = 0.0;
        $proteinasTotal = 0.0;
        $carbohidratosTotal = 0.0;

        foreach ($porcionesCrudas as $nombreCrudo => $gramosRaw) {
            $gramos = (float) $gramosRaw;
            if ($gramos <= 0) {
                continue;
            }

            $clave = $this->normalizarClave((string) $nombreCrudo);
            if (isset($this->tablaNutricional[$clave])) {
                $info = $this->tablaNutricional[$clave];
            } elseif ($esApiExterna) {
                $info = ['calorias' => 1.40, 'proteinas' => 0.08, 'carbohidratos' => 0.16];
            } else {
                continue;
            }

            $caloriasItem = round($info['calorias'] * $gramos, 1);
            $proteinasItem = round($info['proteinas'] * $gramos, 1);
            $carbohidratosItem = round($info['carbohidratos'] * $gramos, 1);

            $caloriasTotal += $caloriasItem;
            $proteinasTotal += $proteinasItem;
            $carbohidratosTotal += $carbohidratosItem;

            $alimentosDetectados[] = [
                'nombre' => $clave,
                'porcion_g' => round($gramos, 1),
                'calorias_kcal' => $caloriasItem,
                'proteinas_g' => $proteinasItem,
                'carbohidratos_g' => $carbohidratosItem,
            ];
        }

        if (empty($alimentosDetectados)) {
            return new ResultadoVision(
                estado: 'ERROR',
                mensaje: 'No se detectaron porciones de alimentos en el plato',
                archivo: $archivo,
                tamanoKb: $tamanoKb,
                proveedor: $proveedor,
            );
        }

        return new ResultadoVision(
            estado: 'OK',
            mensaje: 'Analisis nutricional completado correctamente',
            archivo: $archivo,
            tamanoKb: $tamanoKb,
            alimentos: $alimentosDetectados,
            confianza: round($confianza, 2),
            proveedor: $proveedor,
            caloriasKcal: round($caloriasTotal, 1),
            proteinasG: round($proteinasTotal, 1),
            carbohidratosG: round($carbohidratosTotal, 1),
        );
    }

    private function normalizarClave(string $nombre): string
    {
        $normalizado = mb_strtolower(trim($nombre), 'UTF-8');
        $normalizado = strtr($normalizado, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);
        $normalizado = preg_replace('/[^a-z0-9]+/', '_', $normalizado) ?? $normalizado;
        $clave = trim($normalizado, '_');

        return self::SINONIMOS[$clave] ?? $clave;
    }
}
