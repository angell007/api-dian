<?php

namespace App\Http\Middleware;

use App\Support\DianRuntime;
use Closure;

class EnsureDianMemoryLimit
{
    public function handle($request, Closure $next)
    {
        DianRuntime::applyMemoryLimit();

        return $next($request);
    }
}
