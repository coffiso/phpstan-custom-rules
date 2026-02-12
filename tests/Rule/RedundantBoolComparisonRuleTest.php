<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\RedundantBoolComparisonRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RedundantBoolComparisonRule>
 */
final class RedundantBoolComparisonRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RedundantBoolComparisonRule();
    }

    public function testInvalidCases(): void
    {
        $this->analyse([__DIR__ . '/data/RedundantBoolComparisonTest_invalid.php'], [
            // $boolVar === true/false/null
            ['Redundant comparison: bool expression === true is always resolvable without explicit comparison. Use the bool expression directly.', 13],
            ['Redundant comparison: bool expression === false is always resolvable without explicit comparison. Use the bool expression directly.', 14],
            ['Redundant comparison: bool expression === null is always resolvable without explicit comparison. Use the bool expression directly.', 15],
            // $boolVar == true/false/null
            ['Redundant comparison: bool expression == true is always resolvable without explicit comparison. Use the bool expression directly.', 18],
            ['Redundant comparison: bool expression == false is always resolvable without explicit comparison. Use the bool expression directly.', 19],
            ['Redundant comparison: bool expression == null is always resolvable without explicit comparison. Use the bool expression directly.', 20],
            // $boolVar !== true/false/null
            ['Redundant comparison: bool expression !== true is always resolvable without explicit comparison. Use the bool expression directly.', 23],
            ['Redundant comparison: bool expression !== false is always resolvable without explicit comparison. Use the bool expression directly.', 24],
            ['Redundant comparison: bool expression !== null is always resolvable without explicit comparison. Use the bool expression directly.', 25],
            // $boolVar != true/false/null
            ['Redundant comparison: bool expression != true is always resolvable without explicit comparison. Use the bool expression directly.', 28],
            ['Redundant comparison: bool expression != false is always resolvable without explicit comparison. Use the bool expression directly.', 29],
            ['Redundant comparison: bool expression != null is always resolvable without explicit comparison. Use the bool expression directly.', 30],
            // Reversed order: literal on left
            ['Redundant comparison: bool expression === true is always resolvable without explicit comparison. Use the bool expression directly.', 33],
            ['Redundant comparison: bool expression === false is always resolvable without explicit comparison. Use the bool expression directly.', 34],
            ['Redundant comparison: bool expression == null is always resolvable without explicit comparison. Use the bool expression directly.', 35],
            // Function/method returning bool
            ['Redundant comparison: bool expression === true is always resolvable without explicit comparison. Use the bool expression directly.', 38],
            ['Redundant comparison: bool expression == false is always resolvable without explicit comparison. Use the bool expression directly.', 39],
            ['Redundant comparison: bool expression !== true is always resolvable without explicit comparison. Use the bool expression directly.', 40],
            ['Redundant comparison: bool expression != false is always resolvable without explicit comparison. Use the bool expression directly.', 41],
        ]);
    }

    public function testValidCases(): void
    {
        $this->analyse([__DIR__ . '/data/RedundantBoolComparisonTest_valid.php'], []);
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../rules.neon'];
    }
}
