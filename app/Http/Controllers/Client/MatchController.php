<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatchController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'competition' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $search = trim($filters['q'] ?? '');
        $competition = $filters['competition'] ?? '';

        $query = MatchGame::query()
            ->with(['odds' => fn (HasMany $query) => $query->with('analyses')])
            ->where('date_match', '>=', now()->startOfDay())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $teams) use ($search): void {
                    $teams->where('equipe_a', 'like', '%'.$search.'%')
                        ->orWhere('equipe_b', 'like', '%'.$search.'%')
                        ->orWhere('competition', 'like', '%'.$search.'%');
                });
            })
            ->when($competition !== '', fn (Builder $query) => $query->where('competition', $competition))
            ->orderBy('date_match');

        $matches = $query->paginate(12)->withQueryString();
        $competitions = MatchGame::query()
            ->where('date_match', '>=', now()->startOfDay())
            ->whereNotNull('competition')
            ->distinct()
            ->orderBy('competition')
            ->pluck('competition');

        return view('client.matches.index', compact('matches', 'competitions'));
    }

    public function show(MatchGame $match): View
    {
        $match->load(['odds' => fn (HasMany $query) => $query->with('analyses')]);

        return view('client.matches.show', compact('match'));
    }
}
