<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    /** @param list<string> $roles */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user() && $request->user()->hasRole(...$roles), 403);

        return $next($request);
    }
}
