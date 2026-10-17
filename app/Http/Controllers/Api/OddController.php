<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOddRequest;
use App\Http\Requests\UpdateOddRequest;
use App\Models\AdminActionLog;
use App\Models\MatchGame;
use App\Models\Odd;
use App\Models\OddHistory;
use App\Services\AnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OddController extends Controller
{
    public function __construct(private readonly AnalysisService $analysisService)
    {
    }

    public function forMatch(MatchGame $match): JsonResponse
    {
        return response()->json($match->odds()->with('analyses')->get());
    }

    public function analysis(MatchGame $match): JsonResponse
    {
        return response()->json([
            'match' => $match->load('odds'),
            'analyses' => $match->analyses()->with('odd')->get(),
        ]);
    }

    public function store(StoreOddRequest $request, MatchGame $match): JsonResponse
    {
        $data = $request->safe()->only(['bookmaker', 'cote_1', 'cote_X', 'cote_2', 'probabilites_estimees']);
        $estimates = $data['probabilites_estimees'] ?? null;
        unset($data['probabilites_estimees']);
        $odd = DB::transaction(function () use ($request, $match, $data, $estimates) {
            $odd = $match->odds()->create([...$data, 'source' => 'manuel']);
            $this->analysisService->update($odd, $estimates);
            $this->recordHistory($odd, null, $data, $request);
            $this->log($request, 'ajout_cote', $odd, null, $odd->toArray());

            return $odd;
        });

        return response()->json($odd->load('analyses'), 201);
    }

    public function update(UpdateOddRequest $request, Odd $odd): JsonResponse
    {
        $old = $odd->only(['cote_1', 'cote_X', 'cote_2', 'bookmaker']);
        $new = $request->safe()->only(['bookmaker', 'cote_1', 'cote_X', 'cote_2', 'probabilites_estimees']);
        $estimates = $new['probabilites_estimees'] ?? $odd->analyses()->first()?->probabilites_estimees;
        unset($new['probabilites_estimees']);
        DB::transaction(function () use ($request, $odd, $old, $new, $estimates) {
            $odd->fill([...$new, 'is_modified' => true, 'source' => 'manuel'])->save();
            $this->analysisService->update($odd, $estimates);
            $this->recordHistory($odd, $old, $new + $odd->only(['cote_1', 'cote_X', 'cote_2']), $request);
            $this->log($request, 'modif_cote', $odd, $old, $odd->toArray());
        });

        return response()->json($odd->fresh()->load('analyses'));
    }

    public function destroy(Request $request, Odd $odd): JsonResponse
    {
        $old = $odd->toArray();
        DB::transaction(function () use ($request, $odd, $old) {
            $this->log($request, 'suppr_cote', $odd, $old, null);
            $odd->delete();
        });

        return response()->json(['message' => 'Cote supprimée.']);
    }

    public function valueBets(): JsonResponse
    {
        return response()->json(Odd::query()->with(['match', 'analyses'])->whereHas('analyses', fn ($query) => $query->where('value_bet_detecte', true))->get());
    }

    public function arbitrages(): JsonResponse
    {
        $opportunities = MatchGame::with('odds')->get()->map(function (MatchGame $match) {
            if ($match->odds->isEmpty()) {
                return null;
            }

            $best = [
                '1' => $match->odds->max('cote_1'),
                'X' => $match->odds->max('cote_X'),
                '2' => $match->odds->max('cote_2'),
            ];
            $impliedTotal = array_sum(array_map(fn ($odd) => 1 / $odd, $best));

            return $impliedTotal < 1 ? ['match' => $match, 'meilleures_cotes' => $best, 'rendement_theorique' => (1 / $impliedTotal - 1) * 100] : null;
        })->filter()->values();

        return response()->json($opportunities);
    }

    public function history(int $oddId): JsonResponse
    {
        return response()->json(OddHistory::with('editor')->where('odd_id', $oddId)->orderByDesc('modifie_le')->get());
    }

    private function recordHistory(Odd $odd, ?array $old, array $new, Request $request): void
    {
        OddHistory::create([
            'odd_id' => $odd->id,
            'ancienne_cote_1' => $old['cote_1'] ?? null,
            'nouvelle_cote_1' => $new['cote_1'] ?? null,
            'ancienne_cote_X' => $old['cote_X'] ?? null,
            'nouvelle_cote_X' => $new['cote_X'] ?? null,
            'ancienne_cote_2' => $old['cote_2'] ?? null,
            'nouvelle_cote_2' => $new['cote_2'] ?? null,
            'modifie_par' => $request->user()->id,
            'raison' => $request->input('raison'),
        ]);
    }

    private function log(Request $request, string $action, Odd $odd, ?array $old, ?array $new): void
    {
        AdminActionLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'table_cible' => 'odds',
            'enregistrement_id' => $odd->id,
            'anciennes_valeurs' => $old,
            'nouvelles_valeurs' => $new,
            'raison' => $request->input('raison'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}