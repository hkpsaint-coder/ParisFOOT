<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClient
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'client') {
            abort(403, 'Cet espace est réservé aux comptes client.');
        }

        return $next($request);
    }
}