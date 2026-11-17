<?php

namespace App\Services\VisionIA;

/**
 * Adaptador alternativo para demostrar la intercambiabilidad del proveedor de IA
 * sin modificar controladores ni modulos de autenticacion o menu (Prueba P18 / RNF08).
 */
class ProveedorAlternativoIA implements ServicioVisionIA
{
    private const TAMANO_MAXIMO_KB = 5120;

    /**
     * @var array<string, array{calorias: float, proteinas: float, carbohidratos: float}>
     */
    private array $catalogoAlternativo = [
        'trucha_frita' => ['calorias' => 1.92, 'proteinas' => 0.21, 'carbohidratos' => 0.00],
        'arroz_blanco' => ['calorias' => 1.30, 'proteinas' => 0.027, 'carbohidratos' => 0.28],
        'papa_hervida' => ['calorias' => 0.86, 'proteinas' => 0.02, 'carbohidratos' => 0.20],
        'ensalada' => ['calorias' => 0.25, 'proteinas' => 0.01, 'carbohidratos' => 0.05],
        'pesque_quinua' => ['calorias' => 1.45, 'proteinas' => 0.055, 'carbohidratos' => 0.24],
        'guiso_res' => ['calorias' => 1.85, 'proteinas' => 0.19, 'carbohidratos' => 0.04],
        'pollo_horno' => ['calorias' => 1.65, 'proteinas' => 0.25, 'carbohidratos' => 0.00],
        'lentejas' => ['calorias' => 1.16, 'proteinas' => 0.09, 'carbohidratos' => 0.20],
        'chuno_cocido' => ['calorias' => 1.36, 'proteinas' => 0.019, 'carbohidratos' => 0.32],
    ];

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
                proveedor: 'proveedor-alternativo-ia',
            );
        }

        if ($tamanoKb <= 0 || $tamanoKb > self::TAMANO_MAXIMO_KB) {
            return new ResultadoVision(
                estado: 'ERROR',
                mensaje: 'La imagen supera el tamano maximo de 5 MB (5120 KB)',
                archivo: $archivo,
                tamanoKb: $tamanoKb,
                proveedor: 'proveedor-alternativo-ia',
            );
        }

        if (empty($porcionesGramos) && $rutaImagen !== null && is_file($rutaImagen)) {
            $porcionesGramos = [
                'pesque_quinua' => 180,
                'guiso_res' => 140,
            ];
        }

        $alimentos = [];
        $caloriasTotal = 0.0;
        $proteinasTotal = 0.0;
        $carbohidratosTotal = 0.0;

        foreach ($porcionesGramos as $nombre => $gramosRaw) {
            $gramos = (float) $gramosRaw;
            if ($gramos <= 0 || ! isset($this->catalogoAlternativo[$nombre])) {
                continue;
            }

            $info = $this->catalogoAlternativo[$nombre];
            $caloriasItem = round($info['calorias'] * $gramos, 1);
            $proteinasItem = round($info['proteinas'] * $gramos, 1);
            $carbohidratosItem = round($info['carbohidratos'] * $gramos, 1);

            $caloriasTotal += $caloriasItem;
            $proteinasTotal += $proteinasItem;
            $carbohidratosTotal += $carbohidratosItem;

            $alimentos[] = [
                'nombre' => $nombre,
                'porcion_g' => round($gramos, 1),
                'calorias_kcal' => $caloriasItem,
                'proteinas_g' => $proteinasItem,
                'carbohidratos_g' => $carbohidratosItem,
            ];
        }

        if (empty($alimentos)) {
            return new ResultadoVision(
                estado: 'ERROR',
                mensaje: 'No se detectaron porciones de alimentos en el plato',
                archivo: $archivo,
                tamanoKb: $tamanoKb,
                proveedor: 'proveedor-alternativo-ia',
            );
        }

        return new ResultadoVision(
            estado: 'OK',
            mensaje: 'Analisis completado mediante adaptador alternativo',
            archivo: $archivo,
            tamanoKb: $tamanoKb,
            alimentos: $alimentos,
            confianza: 0.91,
            proveedor: 'proveedor-alternativo-ia',
            caloriasKcal: round($caloriasTotal, 1),
            proteinasG: round($proteinasTotal, 1),
            carbohidratosG: round($carbohidratosTotal, 1),
        );
    }
}
