<?php

/**
 * Banderas de depuración DIAN (solo edición manual en este archivo).
 *
 * Limpiar caché desde el navegador (sin terminal):
 *   /api/ubl2.1/maintenance/clear-cache?key=proh2024
 *   /clear-cache?key=proh2024&format=html
 */
return [
    'preview' => false,
    'verbose' => true,
    'include_xml' => true,
    'memory_limit' => '512M',
];
