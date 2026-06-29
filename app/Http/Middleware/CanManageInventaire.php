<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CanManageInventaire
{
    /**
     * Handle an incoming request.
     * 
     * Vérifie que l'utilisateur est authentifié et a le rôle admin ou agent.
     * Redirige avec un message d'erreur si l'accès est refusé.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier que l'utilisateur est authentifié
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();

        // Vérifier que l'utilisateur a le rôle admin, admin_stock ou agent
        if (!in_array($user->role, ['admin', 'admin_stock', 'agent'])) {
            return redirect()->route('dashboard')
                ->with('error', 'Accès non autorisé');
        }

        return $next($request);
    }
}

