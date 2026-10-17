<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('client.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        $email = mb_strtolower(trim($validated['email']));

        Password::sendResetLink(['email' => $email], function (User $user, string $token): string {
            if ($user->role !== 'client') {
                Password::broker()->deleteToken($user);

                return Password::INVALID_USER;
            }

            $user->sendPasswordResetNotification($token);
            event(new PasswordResetLinkSent($user));

            return Password::RESET_LINK_SENT;
        });

        return back()->with('status', 'Si un compte client correspond à cette adresse, un lien de réinitialisation lui a été envoyé.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('client.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);
        $validated['email'] = mb_strtolower(trim($validated['email']));

        $client = User::query()->where('email', $validated['email'])->where('role', 'client')->first();

        if (! $client) {
            return back()->withErrors(['email' => 'Ce lien de réinitialisation est invalide ou expiré.'])->withInput();
        }

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Ce lien de réinitialisation est invalide ou expiré.'])->withInput();
        }

        return redirect()->route('client.login')->with('status', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.');
    }
}
