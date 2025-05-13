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
        if ($request->cookie('isDay')) {
            config(['app.isday' => filter_var($request->cookie('isDay'), FILTER_VALIDATE_BOOLEAN)]);
        }
        return $next($request);
    }
}
