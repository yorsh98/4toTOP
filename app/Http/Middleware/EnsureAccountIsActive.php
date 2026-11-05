<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Revalida en cada request que la cuenta autenticada siga activa.
 *
 * LoginForm solo comprueba el estado al iniciar sesión; sin este middleware una
 * sesión abierta (o una cookie "recordarme") sobrevive a la suspensión o rechazo.
 */
class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ($user->isApproved() && ! $user->isRejected() && ! $user->isSuspended())) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Rutas públicas (front office): siguen sirviéndose como invitado.
        if (! $this->routeRequiresAuth($request)) {
            return $next($request);
        }

        // Rutas protegidas y acciones de Livewire: cortar aquí, no dejar que el
        // request en curso termine ejecutándose con la identidad anterior.
        if ($request->expectsJson() || $request->headers->has('X-Livewire')) {
            abort(401);
        }

        return redirect()->route('login');
    }

    private function routeRequiresAuth(Request $request): bool
    {
        if ($request->is('livewire/*') || $request->is('livewire-*/*')) {
            return true;
        }

        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (is_string($middleware) && ($middleware === 'auth' || str_starts_with($middleware, 'auth:'))) {
                return true;
            }
        }

        return false;
    }
}
