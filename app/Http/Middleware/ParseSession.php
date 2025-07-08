<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParseSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookie('date')) {
            $request->attributes->set('date', $request->cookie('date'));
        }
        if ($request->cookie('isDay')) {
            $request->attributes->set('isDay', 
            filter_var(
                $request->cookie('isDay'), 
                FILTER_VALIDATE_BOOLEAN
            ));
        }
        return $next($request);
    }
}
