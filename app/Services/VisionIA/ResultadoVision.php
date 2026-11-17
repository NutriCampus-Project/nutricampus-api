<?php

namespace App\Services\VisionIA;

/**
 * Objeto de valor que encapsula el resultado traducido del analisis de vision artificial.
 */
class ResultadoVision
{
    /**
     * @param  array<int, array{nombre: string, porcion_g: float, calorias_kcal: float, proteinas_g: float, carbohidratos_g: float}>  $alimentos
     */
    public function __construct(
        public readonly string $estado,
        public readonly string $mensaje,
        public readonly string $archivo,
        public readonly int $tamanoKb,
        public readonly array $alimentos = [],
        public readonly float $confianza = 0.0,
        public readonly string $proveedor = 'motor-vision-unap',
        public readonly float $caloriasKcal = 0.0,
        public readonly float $proteinasG = 0.0,
        public readonly float $carbohidratosG = 0.0,
    ) {}

    public function esExitoso(): bool
    {
        return $this->estado === 'OK';
    }

    /**
     * Convierte el resultado a un arreglo serializable para respuestas JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (! $this->esExitoso()) {
            return [
                'estado' => $this->estado,
                'mensaje' => $this->mensaje,
                'archivo' => $this->archivo,
                'tamano_kb' => $this->tamanoKb,
                'proveedor' => $this->proveedor,
            ];
        }

        return [
            'estado' => $this->estado,
            'mensaje' => $this->mensaje,
            'archivo' => $this->archivo,
            'tamano_kb' => $this->tamanoKb,
            'proveedor' => $this->proveedor,
            'confianza' => $this->confianza,
            'calorias_kcal' => $this->caloriasKcal,
            'proteinas_g' => $this->proteinasG,
            'carbohidratos_g' => $this->carbohidratosG,
            'alimentos' => $this->alimentos,
        ];
    }
}
