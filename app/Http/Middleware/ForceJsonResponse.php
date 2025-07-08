<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Если ответ уже JsonResponse, пропускаем
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            return $response;
        }

        $original = $response->getOriginalContent();

        $response = response()->json(
            $original,
            $response->getStatusCode(),  // Сохраняем исходный HTTP-код
            $response->headers->all()    // Сохраняем заголовки
        );

        return $response;
    }
}
