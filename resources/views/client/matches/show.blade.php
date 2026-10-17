@extends('client.layout')

@section('title', $match->equipe_a.' - '.$match->equipe_b)

@section('content')
    <a class="back-link" href="{{ route('client.matches.index') }}">← Retour aux matchs</a>
    <header class="page-heading" style="margin-top:20px">
        <div><div class="eyebrow">{{ $match->competition ?: 'Football' }}</div><h1>{{ $match->equipe_a }} <span style="color:#81918a;font-weight:500">vs</span> {{ $match->equipe_b }}</h1><p class="subheading">{{ $match->date_match->locale('fr')->isoFormat('dddd D MMMM YYYY · HH:mm') }}</p></div>
    </header>
    @if ($match->odds->isEmpty())
        <section class="empty"><h2>Cotes indisponibles</h2><p>Les bookmakers n’ont pas encore publié de cotes pour ce match.</p></section>
    @else
        <div class="match-list">
            @foreach ($match->odds as $odd)
                @php($analysis = $odd->analyses->first())
                <article class="match-card">
                    <header class="match-top"><div><div class="competition">Cotes 1X2</div><h2 class="match-title">{{ $odd->bookmaker }}</h2></div><span class="match-date">{{ $odd->source === 'api' ? 'Source automatique' : 'Source manuelle' }}</span></header>
                    <div class="odds-wrap"><table class="odds-table"><thead><tr><th>Issue</th><th>Cote</th><th>Probabilité de réussite</th><th>Probabilité d’échec</th></tr></thead><tbody>
                        <tr><td class="bookmaker">{{ $match->equipe_a }} (1)</td><td class="price">{{ number_format($odd->cote_1, 2, ',', ' ') }}</td><td class="probability">{{ $analysis ? number_format($analysis->proba_1, 1, ',', ' ').'%' : '—' }}</td><td>{{ $analysis ? number_format($analysis->proba_echec_1, 1, ',', ' ').'%' : '—' }}</td></tr>
                        <tr><td class="bookmaker">Match nul (X)</td><td class="price">{{ number_format($odd->cote_X, 2, ',', ' ') }}</td><td class="probability">{{ $analysis ? number_format($analysis->proba_X, 1, ',', ' ').'%' : '—' }}</td><td>{{ $analysis ? number_format($analysis->proba_echec_X, 1, ',', ' ').'%' : '—' }}</td></tr>
                        <tr><td class="bookmaker">{{ $match->equipe_b }} (2)</td><td class="price">{{ number_format($odd->cote_2, 2, ',', ' ') }}</td><td class="probability">{{ $analysis ? number_format($analysis->proba_2, 1, ',', ' ').'%' : '—' }}</td><td>{{ $analysis ? number_format($analysis->proba_echec_2, 1, ',', ' ').'%' : '—' }}</td></tr>
                    </tbody></table></div>
                    @if ($analysis)<p class="subheading" style="padding:0 20px 18px">Marge bookmaker : {{ number_format($analysis->marge_bookmaker, 1, ',', ' ') }}%</p>@endif
                </article>
            @endforeach
        </div>
    @endif
@endsection