<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicBackButtonCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->user() && $request->routeIs('welcome', 'conversions.public', 'Monitor', 'bitacora-pruebas.public', 'servicios.mapa-publico', 'ttypp.welcome')) {
            $response->headers->set('Cache-Control', 'no-cache, private');
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');
        }

        return $response;
    }
}
