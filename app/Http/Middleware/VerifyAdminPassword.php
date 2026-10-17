<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AdminPasswordVerifier;
use Symfony\Component\HttpFoundation\Response;

class VerifyAdminPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            return response()->json(['message' => 'Accès administrateur requis.'], 403);
        }

        $result = app(AdminPasswordVerifier::class)->verify($user, $request->input('admin_password'), $request->ip());
        if ($result === 'limited') {
            return response()->json(['message' => 'Trop de tentatives. Réessayez dans une minute.'], 429);
        }
        if ($result === 'invalid') {
            return response()->json(['message' => 'Mot de passe administrateur invalide.'], 403);
        }

        return $next($request);
    }
}