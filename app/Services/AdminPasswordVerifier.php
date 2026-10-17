<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AdminPasswordVerifier
{
    public function verify(User $user, ?string $password, string $ip): string
    {
        $key = 'admin-password:'.$user->id.':'.$ip;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return 'limited';
        }

        if (! $password || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60);

            return 'invalid';
        }

        RateLimiter::clear($key);

        return 'valid';
    }
}