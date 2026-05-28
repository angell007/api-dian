<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API DIAN — limpieza de caché</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        .ok { color: #0a7; }
        .err { color: #c33; }
        pre { background: #f4f4f4; padding: 1rem; overflow: auto; font-size: 0.9rem; }
        h1 { font-size: 1.25rem; }
    </style>
</head>
<body>
    <h1>Limpieza de caché — API DIAN</h1>
    <p class="{{ ($payload['success'] ?? false) ? 'ok' : 'err' }}">
        <strong>{{ $payload['message'] ?? 'Sin mensaje' }}</strong>
    </p>
    @if (!empty($payload['timestamp']))
        <p>Fecha: {{ $payload['timestamp'] }}</p>
    @endif
    @if (!empty($payload['php_memory_limit']))
        <p>Memoria PHP actual: <code>{{ $payload['php_memory_limit'] }}</code>
        @if (!empty($payload['dian_debug_memory_limit']))
            — configurado en dian_debug: <code>{{ $payload['dian_debug_memory_limit'] }}</code>
        @endif
        </p>
    @endif
    @if (!empty($payload['details']))
        <h2>Detalle</h2>
        <pre>{{ json_encode($payload['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @endif
</body>
</html>
