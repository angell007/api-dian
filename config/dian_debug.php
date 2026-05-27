<?php

/**
 * Banderas de depuración DIAN (solo edición manual en este archivo).
 * No se leen desde el body/query de las peticiones.
 *
 * preview: true  → genera y firma XML, NO envía a la DIAN
 * verbose: true  → escribe detalle en storage/logs/laravel.log
 * include_xml: true → en modo preview, incluye signed_xml en la respuesta JSON
 */
return [
    'preview' => false,
    'verbose' => true,
    'include_xml' => true,
];
