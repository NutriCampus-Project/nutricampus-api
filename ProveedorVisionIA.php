<?php

class ProveedorVisionIA {
    private array $tablaNutricional = [
        'trucha_frita' => ['kcal' => 1.90, 'proteinas' => 0.21, 'carbohidratos' => 0.00],
        'arroz_blanco' => ['kcal' => 1.30, 'proteinas' => 0.027, 'carbohidratos' => 0.28],
        'papa_hervida' => ['kcal' => 0.87, 'proteinas' => 0.019, 'carbohidratos' => 0.20],
        'ensalada'     => ['kcal' => 0.25, 'proteinas' => 0.012, 'carbohidratos' => 0.04],
    ];

    // 1. Recepcion de datos
    public function analizarImagenPlato(string $archivo, int $tamanoKb, array $porcionesGramos): array {
        // 2. Validaciones de formato y tamano maximo (5 MB = 5120 KB)
        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return ['estado' => 'ERROR', 'mensaje' => 'Formato no valido. Solo se permite JPG o PNG.'];
        }
        if ($tamanoKb <= 0 || $tamanoKb > 5120) {
            return ['estado' => 'ERROR', 'mensaje' => 'La imagen supera el tamano maximo de 5 MB (5120 KB).'];
        }
        if (empty($porcionesGramos)) {
            return ['estado' => 'ERROR', 'mensaje' => 'No se detectaron porciones de alimentos en la imagen.'];
        }

        // 3. Procesamiento de la funcionalidad
        $kcal = 0;
        $proteinas = 0;
        $carbohidratos = 0;

        foreach ($porcionesGramos as $alimento => $gramos) {
            if (isset($this->tablaNutricional[$alimento])) {
                $kcal += $gramos * $this->tablaNutricional[$alimento]['kcal'];
                $proteinas += $gramos * $this->tablaNutricional[$alimento]['proteinas'];
                $carbohidratos += $gramos * $this->tablaNutricional[$alimento]['carbohidratos'];
            }
        }

        // 4. Presentacion del resultado
        return [
            'estado'          => 'OK',
            'archivo'         => $archivo,
            'calorias_kcal'   => round($kcal, 1),
            'proteinas_g'     => round($proteinas, 1),
            'carbohidratos_g' => round($carbohidratos, 1)
        ];
    }
}

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