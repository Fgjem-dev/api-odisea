<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       
        // Obtener la API Key del encabezado 'X-API-Key'
        $apiKey = $request->header('X-API-Key');

        // Ontener la API Key esperada desde la configuración (definida en .env)
        $expectedKey = config('app.api_key');

        // verificar si la clave API existe y coincide
        if (!$apiKey || $apiKey !== $expectedKey) {
            return response()->json([
                'message' => 'Unauthorized: Invalid or mising API Key.'
            ], 401); 
        }

              
        return $next($request);
    }
}
