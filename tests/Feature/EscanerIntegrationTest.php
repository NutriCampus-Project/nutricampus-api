<?php

namespace Tests\Feature;

use App\Services\VisionIA\ProveedorAlternativoIA;
use App\Services\VisionIA\ServicioVisionIA;
use Tests\TestCase;

class EscanerIntegrationTest extends TestCase
{
    public function test_integracion_vision_ia_y_calculo_nutricional_exitoso(): void
    {
        $response = $this->postJson('/api/escaneos', [
            'archivo' => 'almuerzo.jpg',
            'tamano_kb' => 2400,
            'porciones' => [
                'trucha_frita' => 150,
                'arroz_blanco' => 100,
                'papa_hervida' => 130,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('estado', 'OK')
            ->assertJsonPath('reporte.calorias_kcal', 528.1)
            ->assertJsonPath('reporte.proteinas_g', 36.7)
            ->assertJsonPath('reporte.carbohidratos_g', 54)
            ->assertJsonCount(3, 'reporte.alimentos');
    }

    public function test_rechazo_imagen_supera_5mb_rf08_rnf02(): void
    {
        $response = $this->postJson('/api/escaneos', [
            'archivo' => 'almuerzo_pesado.jpg',
            'tamano_kb' => 6500,
            'porciones' => [
                'trucha_frita' => 150,
                'arroz_blanco' => 100,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('estado', 'ERROR')
            ->assertJsonPath('mensaje', 'La imagen supera el tamano maximo de 5 MB (5120 KB)');
    }

    public function test_rechazo_formato_imagen_no_permitido_rf08(): void
    {
        $response = $this->postJson('/api/escaneos', [
            'archivo' => 'menu.pdf',
            'tamano_kb' => 1200,
            'porciones' => [
                'trucha_frita' => 150,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('estado', 'ERROR')
            ->assertJsonPath('mensaje', 'Formato de imagen no permitido. Use JPG, JPEG o PNG');
    }

    public function test_reemplazo_adaptador_ia_patron_adapter_rnf08_p18(): void
    {
        $this->app->bind(ServicioVisionIA::class, ProveedorAlternativoIA::class);

        $response = $this->postJson('/api/escaneos', [
            'archivo' => 'almuerzo_alternativo.jpg',
            'tamano_kb' => 1200,
            'porciones' => [
                'pesque_quinua' => 180,
                'guiso_res' => 140,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('estado', 'OK')
            ->assertJsonPath('reporte.proveedor', 'proveedor-alternativo-ia')
            ->assertJsonPath('reporte.calorias_kcal', 520);
    }
}
