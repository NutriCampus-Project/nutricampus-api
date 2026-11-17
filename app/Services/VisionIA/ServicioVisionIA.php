<?php

namespace App\Services\VisionIA;

/**
 * Interfaz objetivo del patron Adapter para desacoplar la API de IA (RNF08).
 *
 * Equivalencia en el Diagrama de Componentes UML (Actividad 1 y 2):
 * representa la interfaz proporcionada {@see ServicioVisionIA} / IAnalisisNutricional
 * consumida por {@see \App\Http\Controllers\EscanerController} (Socket) e
 * implementada por {@see ProveedorVisionIA} (Lollipop).
 */
interface ServicioVisionIA
{
    /**
     * Analiza una imagen de plato y devuelve los alimentos y macronutrientes estimados.
     *
     * Contrato UML: analizarImagen(archivo, tamanoKb, porcionesGramos, rutaImagen): ResultadoVision.
     *
     * @param  array<string, float|int>  $porcionesGramos
     */
    public function analizarImagen(
        string $archivo,
        int $tamanoKb,
        array $porcionesGramos = [],
        ?string $rutaImagen = null
    ): ResultadoVision;
}
