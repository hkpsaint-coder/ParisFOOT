<?php

namespace Tests\Unit;

use App\Services\ProbabilityCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProbabilityCalculatorTest extends TestCase
{
    #[Test]
    public function it_normalizes_implied_probabilities_and_calculates_failures(): void
    {
        $result = (new ProbabilityCalculator())->calculate(2.10, 3.40, 3.60);

        $this->assertSame(['1' => 47.6, 'X' => 29.4, '2' => 27.8], $result['probabilites_implicites']);
        $this->assertSame(4.8, $result['marge_bookmaker']);
        $this->assertSame(['1' => 45.4, 'X' => 28.1, '2' => 26.5], $result['proba_reussite']);
        $this->assertSame(['1' => 54.6, 'X' => 71.9, '2' => 73.5], $result['proba_echec']);
        $this->assertEqualsWithDelta(100, array_sum($result['proba_reussite']), 0.1);
    }

    #[Test]
    public function it_rejects_invalid_odds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ProbabilityCalculator())->calculate(1, 3.4, 3.6);
    }

    #[Test]
    public function it_detects_arbitrage_from_best_prices(): void
    {
        $calculator = new ProbabilityCalculator();

        $this->assertTrue($calculator->detectArbitrage([2.2, 3.6, 3.8]));
        $this->assertFalse($calculator->detectArbitrage([2.0, 3.0, 4.0]));
    }
}