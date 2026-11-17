<?php

class ValidacionMenuService {
    private array $turnosValidos = ['Desayuno', 'Almuerzo', 'Cena'];

    public function validar(string $fecha, string $turno, string $nombrePlato, string $ingredientes): ?string {
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
            return 'Fecha no válida. Use el formato AAAA-MM-DD.';
        }
        if (!in_array($turno, $this->turnosValidos, true)) {
            return 'Turno no válido. Seleccione Desayuno, Almuerzo o Cena.';
        }
        if (trim($nombrePlato) === '' || trim($ingredientes) === '') {
            return 'El nombre del plato y los ingredientes son obligatorios.';
        }
        return null;
    }
}