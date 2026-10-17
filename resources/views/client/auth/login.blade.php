@extends('client.layout')

@section('title', 'Connexion')

@section('content')
    <section class="auth-shell">
        <div class="auth-panel">
            <div class="eyebrow">Espace client</div>
            <h1>Connexion</h1>
            <p>Connectez-vous pour consulter les matchs, les cotes et les probabilités.</p>
            @if (session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
            @if ($errors->any())
                <div class="error-list" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form class="auth-form" method="POST" action="{{ route('client.login.store') }}">
                @csrf
                <div class="field"><label for="email">Adresse e-mail</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus></div>
                <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
                <label class="field" style="display:flex;align-items:center;grid-template-columns:auto 1fr;gap:9px;font-size:13px;color:var(--muted)"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
                <div class="auth-foot" style="margin-top:-8px;text-align:left"><a href="{{ route('client.password.request') }}">Mot de passe oublié ?</a></div>
                <button class="button" type="submit">Se connecter</button>
            </form>
            <div class="auth-foot">Pas encore de compte ? <a href="{{ route('client.register') }}">Créer un compte</a></div>
        </div>
    </section>
@endsection