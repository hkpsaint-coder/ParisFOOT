<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\AppSetting;
use App\Models\Odd;

class AnalysisService
{
    public function __construct(private readonly ProbabilityCalculator $calculator)
    {
    }

    public function update(Odd $odd, ?array $estimates = null): Analysis
    {
        if ($estimates !== null && collect($estimates)->every(fn ($value): bool => blank($value))) {
            $estimates = null;
        }

        if ($estimates !== null) {
            $total = array_sum($estimates);
            if (array_diff(['1', 'X', '2'], array_keys($estimates)) || array_diff(array_keys($estimates), ['1', 'X', '2']) || $total <= 0 || min($estimates) < 0 || max($estimates) > 100) {
                throw new \InvalidArgumentException('Les probabilités estimées fournies sont invalides.');
            }
            $estimates = array_map(fn ($value): float => (float) $value * 100 / $total, $estimates);
        }

        $result = $this->calculator->calculate($odd->cote_1, $odd->cote_X, $odd->cote_2);

        return Analysis::updateOrCreate(
            ['odd_id' => $odd->id],
            [
                'match_id' => $odd->match_id,
                'proba_1' => $result['proba_reussite']['1'],
                'proba_X' => $result['proba_reussite']['X'],
                'proba_2' => $result['proba_reussite']['2'],
                'proba_echec_1' => $result['proba_echec']['1'],
                'proba_echec_X' => $result['proba_echec']['X'],
                'proba_echec_2' => $result['proba_echec']['2'],
                'marge_bookmaker' => $result['marge_bookmaker'],
                'probabilites_estimees' => $estimates,
                'value_bet_detecte' => $estimates !== null && $this->isValueBet($estimates, $result['proba_reussite']),
            ],
        );
    }

    private function isValueBet(array $estimates, array $marketProbabilities): bool
    {
        $minimumEdge = (float) AppSetting::read('value_bet_min_edge', 0);

        foreach (['1', 'X', '2'] as $outcome) {
            if (($estimates[$outcome] ?? 0) >= $marketProbabilities[$outcome] + $minimumEdge && ($estimates[$outcome] ?? 0) > $marketProbabilities[$outcome]) {
                return true;
            }
        }

        return false;
    }
}