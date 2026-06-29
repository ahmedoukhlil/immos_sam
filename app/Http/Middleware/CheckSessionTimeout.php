<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * Vérifie si la session a expiré après 30 minutes d'inactivité
     * et déconnecte automatiquement l'utilisateur si nécessaire.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (Auth::check()) {
            $lastActivity = session('last_activity');
            $timeout = 30 * 60; // 30 minutes en secondes

            // Si last_activity n'existe pas, l'initialiser
            if (!$lastActivity) {
                session(['last_activity' => now()->timestamp]);
            } else {
                // Vérifier si le timeout a été dépassé
                $timeSinceLastActivity = now()->timestamp - $lastActivity;

                if ($timeSinceLastActivity > $timeout) {
                    // Session expirée - déconnecter l'utilisateur
                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    // Rediriger vers la page de connexion avec un message
                    return redirect()->route('login')
                        ->with('error', 'Votre session a expiré après 30 minutes d\'inactivité. Veuillez vous reconnecter.');
                }
            }

            // Mettre à jour le timestamp de dernière activité
            session(['last_activity' => now()->timestamp]);
        }

        return $next($request);
    }
}

