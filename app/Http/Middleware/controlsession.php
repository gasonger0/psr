<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class controlsession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookie('date')) {
            config(['app.date' => $request->cookie('date')]);
        }
        if (boolval($request->cookie('isDay'))) {
            config(['app.isday' => boolval($request->cookie('isDay'))]);
        }
        return $next($request);
    }
}
