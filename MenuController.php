<?php

require_once 'ValidacionMenuService.php';
require_once 'MenuRepository.php';

class MenuController {
    public function __construct(
        private ValidacionMenuService $validador,
        private MenuRepository $repositorio
    ) {}

    // 1. Recepción de datos
    public function publicarMenuDiario(
        string $rolUsuario,
        string $fecha,
        string $turno,
        string $nombrePlato,
        string $ingredientes
    ): array {
        // 2. Validación de rol y campos obligatorios
        if ($rolUsuario !== 'Encargado') {
            return ['estado' => 'ERROR', 'mensaje' => 'Acceso denegado: solo el encargado del comedor puede publicar el menú.'];
        }
        $error = $this->validador->validar($fecha, $turno, $nombrePlato, $ingredientes);
        if ($error !== null) {
            return ['estado' => 'ERROR', 'mensaje' => $error];
        }

        // 3. Procesamiento: se guarda en MySQL mediante el repositorio
        $idMenu = $this->repositorio->guardar([
            'fecha' => $fecha,
            'turno' => $turno,
            'plato_principal' => trim($nombrePlato),
            'ingredientes' => trim($ingredientes),
            'publicado_por' => $rolUsuario
        ]);

        // 4. Presentación del resultado
        return [
            'estado' => 'OK',
            'mensaje' => 'Menú diario publicado correctamente.',
            'datos' => ['id_menu' => $idMenu, 'fecha' => $fecha, 'turno' => $turno]
        ];
    }
}