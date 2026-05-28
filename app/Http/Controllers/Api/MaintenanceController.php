<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\DianRuntime;
use Artisan;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     * Limpia cachés de Laravel (config, vistas Blade, rutas, etc.).
     * Uso: GET /api/ubl2.1/maintenance/clear-cache?key=CLAVE
     *      GET /clear-cache?key=CLAVE  (ruta web)
     */
    public function clearCache(Request $request)
    {
        $expectedKey = (string) env('DIAN_CACHE_CLEAR_KEY', 'proh2024');
        $providedKey = (string) $request->query('key', '');

        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            return $this->respond($request, [
                'success' => false,
                'message' => 'Clave inválida. Agrega ?key=TU_CLAVE a la URL.',
                'hint' => 'Defina DIAN_CACHE_CLEAR_KEY en .env o use la clave por defecto documentada.',
            ], 403);
        }

        $details = [];
        $errors = [];

        foreach ($this->commandsToRun() as $command) {
            try {
                Artisan::call($command);
                $details[$command] = trim(Artisan::output()) ?: 'OK';
            } catch (\Throwable $e) {
                $details[$command] = 'Error: ' . $e->getMessage();
                $errors[] = $command;
            }
        }

        DianRuntime::applyMemoryLimit();

        $payload = [
            'success' => empty($errors),
            'message' => empty($errors)
                ? 'Caché limpiada correctamente. Vuelva a intentar el envío a la DIAN.'
                : 'Caché limpiada con advertencias en: ' . implode(', ', $errors),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'php_memory_limit' => ini_get('memory_limit'),
            'dian_debug_memory_limit' => config('dian_debug.memory_limit'),
            'details' => $details,
        ];

        return $this->respond($request, $payload, empty($errors) ? 200 : 207);
    }

    /**
     * @return string[]
     */
    private function commandsToRun(): array
    {
        return [
            'view:clear',
            'cache:clear',
            'config:clear',
            'route:clear',
            'optimize:clear',
        ];
    }

    private function respond(Request $request, array $payload, int $status = 200)
    {
        if ($request->query('format') === 'html' || $request->accepts('text/html')) {
            return response()->view('maintenance.clear-cache-result', [
                'payload' => $payload,
                'status' => $status,
            ], $status);
        }

        return response()->json($payload, $status);
    }
}
