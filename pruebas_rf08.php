<?php

require __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\EscanerController;
use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

/** @var EscanerController $controlador */
$controlador = $app->make(EscanerController::class);

echo "=================================================================================\n";
echo "NUTRICAMPUS API - INTEGRACION DE COMPONENTES (Escaner -> ServicioVisionIA -> IA)\n";
echo "=================================================================================\n\n";

echo "=== PRUEBA 1: FUNCIONAMIENTO ESPERADO - RF08 y RF09 (almuerzo_unap.jpg - 2400 KB) ===\n";
$resultado1 = $controlador->escanearPlato(
    archivo: 'almuerzo_unap.jpg',
    tamanoKb: 2400,
    porciones: [
        'trucha_frita' => 150,
        'arroz_blanco' => 100,
        'papa_hervida' => 130,
    ]
);
echo json_encode($resultado1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== PRUEBA 2: RECHAZO POR TAMANO > 5 MB - RNF02 (almuerzo_pesado.jpg - 6500 KB) ===\n";
$resultado2 = $controlador->escanearPlato(
    archivo: 'almuerzo_pesado.jpg',
    tamanoKb: 6500,
    porciones: [
        'trucha_frita' => 150,
        'arroz_blanco' => 100,
    ]
);
echo json_encode($resultado2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== PRUEBA 3: RECHAZO POR FORMATO NO PERMITIDO - RF08 (documento.pdf - 1200 KB) ===\n";
$resultado3 = $controlador->escanearPlato(
    archivo: 'documento.pdf',
    tamanoKb: 1200,
    porciones: [
        'trucha_frita' => 150,
    ]
);
echo json_encode($resultado3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
