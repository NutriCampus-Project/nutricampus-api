<?php

class MenuController {
    private array $turnosValidos = ['Desayuno', 'Almuerzo', 'Cena'];

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
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            return ['estado' => 'ERROR', 'mensaje' => 'Fecha no válida. Use el formato AAAA-MM-DD.'];
        }
        if (!in_array($turno, $this->turnosValidos, true)) {
            return ['estado' => 'ERROR', 'mensaje' => 'Turno no válido. Seleccione Desayuno, Almuerzo o Cena.'];
        }
        if (trim($nombrePlato) === '' || trim($ingredientes) === '') {
            return ['estado' => 'ERROR', 'mensaje' => 'El nombre del plato y los ingredientes son obligatorios.'];
        }

        // 3. Procesamiento de la funcionalidad
        $menuRegistrado = [
            'id_menu' => rand(100, 999),
            'fecha' => $fecha,
            'turno' => $turno,
            'plato_principal' => trim($nombrePlato),
            'ingredientes' => trim($ingredientes),
            'publicado_por' => $rolUsuario
        ];

        // 4. Presentación del resultado
        return ['estado' => 'OK', 'mensaje' => 'Menú diario publicado correctamente.', 'datos' => $menuRegistrado];
    }
}

// Ejecución de los casos de prueba (Actividad 3)
$controlador = new MenuController();

echo "=== CASO VALIDO ===\n";
print_r($controlador->publicarMenuDiario('Encargado', '2026-10-30', 'Almuerzo', 'Pesque de quinua con guiso de res', 'Quinua, carne de res, queso, leche, arroz'));

echo "\n=== CASO INVALIDO ===\n";
print_r($controlador->publicarMenuDiario('Estudiante', '2026-10-30', 'Almuerzo', 'Trucha frita', 'Trucha, papa, ensalada'));
