<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatchRequest;
use App\Http\Requests\UpdateMatchRequest;
use App\Models\AdminActionLog;
use App\Models\MatchGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $matches = MatchGame::query()
            ->with('odds')
            ->when($request->date, fn ($query, $date) => $query->whereDate('date_match', $date))
            ->when($request->competition, fn ($query, $competition) => $query->where('competition', $competition))
            ->when($request->source, fn ($query, $source) => $query->where('source', $source))
            ->orderBy('date_match')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json($matches);
    }

    public function show(MatchGame $match): JsonResponse
    {
        return response()->json($match->load(['odds.analyses', 'creator']));
    }

    public function store(StoreMatchRequest $request): JsonResponse
    {
        $match = DB::transaction(function () use ($request): MatchGame {
            $match = MatchGame::create([
                ...$request->validated(),
                'source' => 'manuel',
                'created_by' => $request->user()->id,
            ]);
            $this->log($request, 'ajout_match', 'matches', $match->id, null, $match->toArray());

            return $match;
        });

        return response()->json($match, 201);
    }

    public function update(UpdateMatchRequest $request, MatchGame $match): JsonResponse
    {
        DB::transaction(function () use ($request, $match): void {
            $old = $match->only(['equipe_a', 'equipe_b', 'date_match', 'competition']);
            $match->fill($request->validated())->save();
            $this->log($request, 'modif_match', 'matches', $match->id, $old, $match->only(array_keys($old)));
        });

        return response()->json($match->fresh());
    }

    public function destroy(Request $request, MatchGame $match): JsonResponse
    {
        DB::transaction(function () use ($request, $match): void {
            $old = $match->toArray();
            $this->log($request, 'suppr_match', 'matches', $match->id, $old, null);
            $match->delete();
        });

        return response()->json(['message' => 'Match supprimé.']);
    }

    private function log(Request $request, string $action, string $table, int $id, ?array $old, ?array $new): void
    {
        AdminActionLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'table_cible' => $table,
            'enregistrement_id' => $id,
            'anciennes_valeurs' => $old,
            'nouvelles_valeurs' => $new,
            'raison' => $request->input('raison'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}