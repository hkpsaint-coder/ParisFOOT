@extends('client.layout')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
    <section class="auth-shell">
        <div class="auth-panel">
            <div class="eyebrow">Espace client</div>
            <h1>Nouveau mot de passe</h1>
            <p>Choisissez un nouveau mot de passe d’au moins 10 caractères.</p>
            @if ($errors->any())
                <div class="error-list" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form class="auth-form" method="POST" action="{{ route('client.password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="field"><label for="email">Adresse e-mail</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $email) }}" required></div>
                <div class="field"><label for="password">Nouveau mot de passe</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required></div>
                <div class="field"><label for="password_confirmation">Confirmer le mot de passe</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
                <button class="button" type="submit">Réinitialiser le mot de passe</button>
            </form>
        </div>
    </section>
@endsection
