<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function() {
    return redirect('/api/ubl2.1/documentation');
});

Route::get('/listings', 'ListingController@index');

Route::get('/clear-cache', function () {
    // Clave de seguridad simple para evitar acceso no autorizado
    $secretKey = 'proh2024';

    if (request('key') !== $secretKey) {
        return response()->json([
            'success' => false,
            'message' => 'Clave de acceso inválida. Usa: /clear-cache?key=TU_CLAVE'
        ], 403);
    }

    try {
        // Limpiar todas las cachés
        Artisan::call('view:clear');
        $viewClear = Artisan::output();

        Artisan::call('cache:clear');
        $cacheClear = Artisan::output();

        Artisan::call('config:clear');
        $configClear = Artisan::output();

        Artisan::call('route:clear');
        $routeClear = Artisan::output();

        // Intentar limpiar optimize (puede fallar en algunas versiones)
        try {
            Artisan::call('optimize:clear');
            $optimizeClear = Artisan::output();
        } catch (\Exception $e) {
            $optimizeClear = 'No disponible en esta versión';
        }

        return response()->json([
            'success' => true,
            'message' => 'Caché limpiada correctamente',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'details' => [
                'view:clear' => trim($viewClear),
                'cache:clear' => trim($cacheClear),
                'config:clear' => trim($configClear),
                'route:clear' => trim($routeClear),
                'optimize:clear' => trim($optimizeClear),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al limpiar caché: ' . $e->getMessage()
        ], 500);
    }
});
