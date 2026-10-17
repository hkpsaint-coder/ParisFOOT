@extends('client.layout')

@section('title', 'Mot de passe oublié')

@section('content')
    <section class="auth-shell">
        <div class="auth-panel">
            <div class="eyebrow">Espace client</div>
            <h1>Mot de passe oublié</h1>
            <p>Indiquez l’adresse e-mail associée à votre compte. Si un compte client existe, vous recevrez un lien de réinitialisation.</p>
            @if (session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
            @if ($errors->any())
                <div class="error-list" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form class="auth-form" method="POST" action="{{ route('client.password.email') }}">
                @csrf
                <div class="field"><label for="email">Adresse e-mail</label><input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus></div>
                <button class="button" type="submit">Envoyer le lien</button>
            </form>
            <div class="auth-foot"><a href="{{ route('client.login') }}">Retour à la connexion</a></div>
        </div>
    </section>
@endsection
