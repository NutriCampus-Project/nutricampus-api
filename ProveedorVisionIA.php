<?php

class ProveedorVisionIA {
    private const MAX_TAMANIO_KB = 5120;

    private array $tablaNutricional = [
        'trucha_frita' => ['kcal' => 1.90, 'proteinas' => 0.21, 'carbohidratos' => 0.00],
        'arroz_blanco' => ['kcal' => 1.30, 'proteinas' => 0.027, 'carbohidratos' => 0.28],
        'papa_hervida' => ['kcal' => 0.87, 'proteinas' => 0.019, 'carbohidratos' => 0.20],
        'ensalada'     => ['kcal' => 0.25, 'proteinas' => 0.012, 'carbohidratos' => 0.04],
    ];

    public function analizarImagenPlato(string $archivo, $tamanoKb, array $porcionesGramos): array {
        $archivo = trim($archivo);
        if ($archivo === '') {
            return ['estado' => 'ERROR', 'mensaje' => 'Debe indicar el nombre o la ruta de la imagen.'];
        }

        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return ['estado' => 'ERROR', 'mensaje' => 'Formato no valido. Solo se permite JPG o PNG.'];
        }

        if (!is_numeric($tamanoKb) || (float) $tamanoKb <= 0 || (float) $tamanoKb > self::MAX_TAMANIO_KB) {
            return ['estado' => 'ERROR', 'mensaje' => 'La imagen supera el tamano maximo de 5 MB (5120 KB).'];
        }

        if ($porcionesGramos === []) {
            return ['estado' => 'ERROR', 'mensaje' => 'No se detectaron porciones de alimentos en la imagen.'];
        }

        $kcal = 0.0;
        $proteinas = 0.0;
        $carbohidratos = 0.0;

        foreach ($porcionesGramos as $alimento => $gramos) {
            $alimentoNormalizado = strtolower(trim((string) $alimento));
            if (!is_numeric($gramos) || (float) $gramos <= 0 || !isset($this->tablaNutricional[$alimentoNormalizado])) {
                continue;
            }

            $gramosNumericos = (float) $gramos;
            $datosNutricionales = $this->tablaNutricional[$alimentoNormalizado];

            $kcal += $gramosNumericos * $datosNutricionales['kcal'];
            $proteinas += $gramosNumericos * $datosNutricionales['proteinas'];
            $carbohidratos += $gramosNumericos * $datosNutricionales['carbohidratos'];
        }

        if ($kcal === 0.0 && $proteinas === 0.0 && $carbohidratos === 0.0) {
            return ['estado' => 'ERROR', 'mensaje' => 'No se encontraron alimentos validos para estimar el plato.'];
        }

        return [
            'estado'          => 'OK',
            'archivo'         => $archivo,
            'calorias_kcal'   => round($kcal, 1),
            'proteinas_g'     => round($proteinas, 1),
            'carbohidratos_g' => round($carbohidratos, 1)
        ];
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $ia = new ProveedorVisionIA();
    echo "=== CASO VALIDO ===\n";
    print_r($ia->analizarImagenPlato('almuerzo.jpg', 2400, [
        'trucha_frita' => 150,
        'arroz_blanco' => 100,
        'papa_hervida' => 130
    ]));

    echo "\n=== CASO INVALIDO ===\n";
    print_r($ia->analizarImagenPlato('almuerzo_pesado.jpg', 6500, [
        'trucha_frita' => 150
    ]));
}