<?php

namespace App\Http\Controllers;

use App\Services\VisionIA\ServicioVisionIA;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del escaner nutricional con IA bajo el patron Adapter (Figura 7).
 */
class EscanerController extends Controller
{
    public function __construct(
        private readonly ServicioVisionIA $servicioIA
    ) {}

    /**
     * Endpoint POST /api/escaneos
     * Integra EscanerController -> ServicioVisionIA -> ProveedorVisionIA -> ClienteApiExterna -> ResultadoVision (RF08, RF09).
     * Soporta subida multipart (foto), codificacion imagen_base64 desde Flutter y metadatos JSON.
     */
    public function escanear(Request $request): JsonResponse
    {
        $rutaImagen = null;
        $archivoTemporal = null;

        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $archivo = (string) ($request->input('archivo') ?: $foto->getClientOriginalName());
            $tamanoKb = (int) ($request->input('tamano_kb') ?: max(1, (int) ceil($foto->getSize() / 1024)));
            $rutaImagen = $foto->getRealPath() ?: null;
        } elseif ($request->filled('imagen_base64')) {
            $decodificada = $this->prepararImagenBase64(
                (string) $request->input('imagen_base64'),
                $request->input('archivo')
            );
            if ($decodificada === null) {
                return response()->json([
                    'estado' => 'ERROR',
                    'mensaje' => 'Contenido imagen_base64 invalido o vacio',
                    'archivo' => (string) $request->input('archivo', ''),
                    'tamano_kb' => 0,
                    'proveedor' => 'proveedor-vision-ia',
                ], 422);
            }

            $archivo = $decodificada['archivo'];
            $tamanoKb = $request->filled('tamano_kb')
                ? (int) $request->input('tamano_kb')
                : $decodificada['tamano_kb'];
            $rutaImagen = $decodificada['ruta_temporal'];
            $archivoTemporal = $decodificada['ruta_temporal'];
        } else {
            $archivo = (string) $request->input('archivo', '');
            $tamanoKb = (int) $request->input('tamano_kb', 0);
        }

        $porcionesRaw = $request->input('porciones', []);
        if (is_string($porcionesRaw)) {
            $porciones = json_decode($porcionesRaw, true) ?? [];
        } elseif (is_array($porcionesRaw)) {
            $porciones = $porcionesRaw;
        } else {
            $porciones = [];
        }

        try {
            $resultado = $this->servicioIA->analizarImagen($archivo, $tamanoKb, $porciones, $rutaImagen);
        } finally {
            if ($archivoTemporal !== null && is_file($archivoTemporal)) {
                @unlink($archivoTemporal);
            }
        }

        if (! $resultado->esExitoso()) {
            return response()->json($resultado->toArray(), 422);
        }

        return response()->json([
            'estado' => 'OK',
            'mensaje' => 'Analisis nutricional con IA completado correctamente.',
            'reporte' => $resultado->toArray(),
        ], 200);
    }

    /**
     * Decodifica una imagen enviada en Base64 o Data URI hacia un archivo temporal seguro.
     *
     * @return array{archivo: string, tamano_kb: int, ruta_temporal: ?string}|null
     */
    private function prepararImagenBase64(string $rawBase64, mixed $nombreExplicito): ?array
    {
        $extensionDetectada = 'jpg';
        $payload = trim($rawBase64);

        if (preg_match('/^data:image\/([a-zA-Z0-9]+);base64,(.+)$/s', $payload, $coincidencias) === 1) {
            $subtipo = strtolower($coincidencias[1]);
            $extensionDetectada = $subtipo === 'jpeg' ? 'jpg' : $subtipo;
            $payload = $coincidencias[2];
        }

        $binario = base64_decode($payload, true);
        if ($binario === false || $binario === '') {
            return null;
        }

        $tamanoKb = max(1, (int) ceil(strlen($binario) / 1024));
        $archivo = is_string($nombreExplicito) && trim($nombreExplicito) !== ''
            ? trim($nombreExplicito)
            : "captura_plato.{$extensionDetectada}";

        $rutaTemporal = null;
        $extArchivo = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if ($tamanoKb <= 5120 && in_array($extArchivo, ['jpg', 'jpeg', 'png'], true)) {
            $base = tempnam(sys_get_temp_dir(), 'nutricampus_');
            if ($base !== false) {
                $rutaConExtension = "{$base}.{$extArchivo}";
                if (@rename($base, $rutaConExtension) && file_put_contents($rutaConExtension, $binario) !== false) {
                    $rutaTemporal = $rutaConExtension;
                } else {
                    @unlink($base);
                    @unlink($rutaConExtension);
                }
            }
        }

        return [
            'archivo' => $archivo,
            'tamano_kb' => $tamanoKb,
            'ruta_temporal' => $rutaTemporal,
        ];
    }

    /**
     * Metodo auxiliar para invocacion directa desde scripts CLI de prueba e integracion.
     *
     * @param  array<string, float|int>  $porciones
     * @return array<string, mixed>
     */
    public function escanearPlato(
        string $archivo,
        int $tamanoKb,
        array $porciones = [],
        ?string $rutaImagen = null
    ): array {
        $resultado = $this->servicioIA->analizarImagen($archivo, $tamanoKb, $porciones, $rutaImagen);

        if (! $resultado->esExitoso()) {
            return array_merge(['http_status' => 422], $resultado->toArray());
        }

        return [
            'http_status' => 200,
            'estado' => 'OK',
            'mensaje' => 'Analisis nutricional con IA completado correctamente.',
            'reporte' => $resultado->toArray(),
        ];
    }
}
