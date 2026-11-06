<?php
require_once __DIR__ . '/ProveedorVisionIA.php';

class EscanerController
{
    private ProveedorVisionIA $servicioIA;

    public function __construct(ProveedorVisionIA $servicioIA)
    {
        $this->servicioIA = $servicioIA;
    }

    public function escanearPlato(string $archivo, int $tamanoKb, array $porciones): array
    {
        return $this->servicioIA->analizarImagenPlato($archivo, $tamanoKb, $porciones);
    }
}

$controlador = new EscanerController(new ProveedorVisionIA());

echo "=== PRUEBA 1: DATOS CORRECTOS (almuerzo.jpg - 2400 KB) ===\n";
$resultado1 = $controlador->escanearPlato('almuerzo.jpg', 2400, [
    'trucha_frita' => 150,
    'arroz_blanco' => 100,
    'papa_hervida' => 130
]);
print_r($resultado1);

echo "\n=== PRUEBA 2: DATOS QUE DEBEN SER RECHAZADOS (almuerzo_pesado.jpg - 6500 KB) ===\n";
$resultado2 = $controlador->escanearPlato('almuerzo_pesado.jpg', 6500, [
    'trucha_frita' => 150,
    'arroz_blanco' => 100
]);
print_r($resultado2);
