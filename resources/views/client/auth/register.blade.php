@extends('client.layout')

@section('title', 'Créer un compte')

@section('content')
    <section class="auth-shell">
        <div class="auth-panel">
            <div class="eyebrow">Espace client</div>
            <h1>Créer un compte</h1>
            <p>Inscrivez-vous pour consulter les matchs et leurs analyses de cotes.</p>
            @if ($errors->any())
                <div class="error-list"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form class="auth-form" method="POST" action="{{ route('client.register.store') }}">
                @csrf
                <div class="field"><label for="name">Nom</label><input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" maxlength="120" required autofocus></div>
                <div class="field"><label for="email">Adresse e-mail</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required></div>
                <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required><small class="subheading">10 caractères minimum.</small></div>
                <div class="field"><label for="password_confirmation">Confirmer le mot de passe</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
                <button class="button" type="submit">Créer mon compte</button>
            </form>
            <div class="auth-foot">Déjà inscrit ? <a href="{{ route('client.login') }}">Se connecter</a></div>
        </div>
    </section>
@endsection