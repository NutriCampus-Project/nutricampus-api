<?php

class ProveedorVisionIA
{
    private array $tablaNutricional = [
        'trucha_frita' => ['calorias' => 1.90, 'proteinas' => 0.21,  'carbohidratos' => 0.00],
        'arroz_blanco' => ['calorias' => 1.30, 'proteinas' => 0.026, 'carbohidratos' => 0.28],
        'papa_hervida' => ['calorias' => 0.87, 'proteinas' => 0.02,  'carbohidratos' => 0.20],
        'ensalada'     => ['calorias' => 0.25, 'proteinas' => 0.01,  'carbohidratos' => 0.05],
    ];

    public function analizarImagenPlato(string $archivo, int $tamanoKb, array $porcionesGramos): array
    {
        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return [
                'estado'  => 'ERROR',
                'mensaje' => 'Formato de imagen no permitido. Use JPG, JPEG o PNG'
            ];
        }

        if ($tamanoKb <= 0 || $tamanoKb > 5120) {
            return [
                'estado'  => 'ERROR',
                'mensaje' => 'La imagen supera el tamano maximo de 5 MB (5120 KB)'
            ];
        }

        if (empty($porcionesGramos)) {
            return [
                'estado'  => 'ERROR',
                'mensaje' => 'No se detectaron porciones de alimentos en el plato'
            ];
        }

        $calorias = 0.0;
        $proteinas = 0.0;
        $carbohidratos = 0.0;

        foreach ($porcionesGramos as $alimento => $gramos) {
            if (isset($this->tablaNutricional[$alimento]) && $gramos > 0) {
                $info = $this->tablaNutricional[$alimento];
                $calorias      += $info['calorias'] * $gramos;
                $proteinas     += $info['proteinas'] * $gramos;
                $carbohidratos += $info['carbohidratos'] * $gramos;
            }
        }

        return [
            'estado'          => 'OK',
            'archivo'         => $archivo,
            'tamano_kb'       => $tamanoKb,
            'calorias_kcal'   => round($calorias, 1),
            'proteinas_g'     => round($proteinas, 1),
            'carbohidratos_g' => round($carbohidratos, 1)
        ];
    }
}
