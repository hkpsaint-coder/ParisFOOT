<?php

namespace App\Services;

use InvalidArgumentException;

class ProbabilityCalculator
{
    public function calculate(float $cote1, float $coteX, float $cote2): array
    {
        $cotes = ['1' => $cote1, 'X' => $coteX, '2' => $cote2];

        foreach ($cotes as $cote) {
            if (! is_finite($cote) || $cote <= 1) {
                throw new InvalidArgumentException('Chaque cote doit être un nombre fini supérieur à 1.');
            }
        }

        $implicites = array_map(fn (float $cote): float => 100 / $cote, $cotes);
        $somme = array_sum($implicites);
        $normalisees = array_map(fn (float $proba): float => $proba / $somme * 100, $implicites);
        $echecs = array_map(fn (float $proba): float => 100 - $proba, $normalisees);

        return [
            'probabilites_implicites' => array_map(fn (float $value): float => round($value, 1), $implicites),
            'somme_probabilites_implicites' => round($somme, 1),
            'marge_bookmaker' => round($somme - 100, 1),
            'probabilites_normalisees' => array_map(fn (float $value): float => round($value, 1), $normalisees),
            'proba_reussite' => array_map(fn (float $value): float => round($value, 1), $normalisees),
            'proba_echec' => array_map(fn (float $value): float => round($value, 1), $echecs),
        ];
    }

    public function detectArbitrage(array $bookmakerOdds): bool
    {
        if (count($bookmakerOdds) < 3 || min($bookmakerOdds) <= 1) {
            return false;
        }

        return array_sum(array_map(fn (float $odd): float => 1 / $odd, $bookmakerOdds)) < 1;
    }
}