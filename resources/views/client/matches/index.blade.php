@extends('client.layout')

@section('title', 'Matchs à venir')

@section('content')
    <header class="page-heading">
        <div><div class="eyebrow">Données de marché</div><h1>Matchs à venir</h1><p class="subheading">Cotes 1X2 et probabilités de réussite estimées par bookmaker.</p></div>
    </header>

    @if (session('status'))<div class="status">{{ session('status') }}</div>@endif

    <form class="toolbar" method="GET" action="{{ route('client.matches.index') }}">
        <div class="field" style="flex:1"><label for="q">Rechercher un match ou une compétition</label><input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Équipe, derby, Ligue 1..."></div>
        <div class="field"><label for="competition">Compétition</label><select id="competition" name="competition"><option value="">Toutes</option>@foreach ($competitions as $competition)<option value="{{ $competition }}" @selected(request('competition') === $competition)>{{ $competition }}</option>@endforeach</select></div>
        <button class="button" type="submit">Filtrer</button>
        @if (request()->hasAny(['q', 'competition']))<a class="button button-secondary" href="{{ route('client.matches.index') }}">Effacer</a>@endif
    </form>

    @if ($matches->isEmpty())
        <section class="empty"><h2>Aucun match disponible</h2><p>Il n’y a pas de match correspondant aux filtres pour le moment.</p></section>
    @else
        <div class="match-list">
            @foreach ($matches as $match)
                <article class="match-card">
                    <header class="match-top">
                        <div><div class="competition">{{ $match->competition ?: 'Football' }}</div><h2 class="match-title">{{ $match->equipe_a }} <span style="color:#81918a;font-weight:500">vs</span> {{ $match->equipe_b }}</h2></div>
                        <time class="match-date" datetime="{{ $match->date_match->toIso8601String() }}">{{ $match->date_match->locale('fr')->isoFormat('ddd D MMM · HH:mm') }}</time>
                    </header>
                    @if ($match->odds->isNotEmpty())
                        <div class="odds-wrap"><table class="odds-table">
                            <thead><tr><th>Bookmaker</th><th>Cote 1</th><th>Réussite 1</th><th>Cote X</th><th>Réussite X</th><th>Cote 2</th><th>Réussite 2</th></tr></thead>
                            <tbody>
                                @foreach ($match->odds as $odd)
                                    @php($analysis = $odd->analyses->first())
                                    <tr><td class="bookmaker">{{ $odd->bookmaker }}</td><td class="price">{{ number_format($odd->cote_1, 2, ',', ' ') }}</td><td class="probability">{{ $analysis ? number_format($analysis->proba_1, 1, ',', ' ').'%' : '—' }}</td><td class="price">{{ number_format($odd->cote_X, 2, ',', ' ') }}</td><td class="probability">{{ $analysis ? number_format($analysis->proba_X, 1, ',', ' ').'%' : '—' }}</td><td class="price">{{ number_format($odd->cote_2, 2, ',', ' ') }}</td><td class="probability">{{ $analysis ? number_format($analysis->proba_2, 1, ',', ' ').'%' : '—' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @else
                        <p class="subheading" style="padding:0 20px 16px">Les cotes ne sont pas encore disponibles.</p>
                    @endif
                    <a class="detail-link" href="{{ route('client.matches.show', $match) }}">Voir le match <span aria-hidden="true">→</span></a>
                </article>
            @endforeach
        </div>
        <div class="pagination">{{ $matches->links() }}</div>
    @endif
@endsection