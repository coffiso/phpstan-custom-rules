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
            // $boolVar === true/false
            ['Redundant comparison: bool expression === true is always resolvable without explicit comparison. Use the bool expression directly.', 13],
            ['Redundant comparison: bool expression === false is always resolvable without explicit comparison. Use the bool expression directly.', 14],
            // $boolVar == true/false
            ['Redundant comparison: bool expression == true is always resolvable without explicit comparison. Use the bool expression directly.', 17],
            ['Redundant comparison: bool expression == false is always resolvable without explicit comparison. Use the bool expression directly.', 18],
            // $boolVar !== true/false
            ['Redundant comparison: bool expression !== true is always resolvable without explicit comparison. Use the bool expression directly.', 21],
            ['Redundant comparison: bool expression !== false is always resolvable without explicit comparison. Use the bool expression directly.', 22],
            // $boolVar != true/false
            ['Redundant comparison: bool expression != true is always resolvable without explicit comparison. Use the bool expression directly.', 25],
            ['Redundant comparison: bool expression != false is always resolvable without explicit comparison. Use the bool expression directly.', 26],
            // Reversed order: literal on left
            ['Redundant comparison: bool expression === true is always resolvable without explicit comparison. Use the bool expression directly.', 29],
            ['Redundant comparison: bool expression === false is always resolvable without explicit comparison. Use the bool expression directly.', 30],
            // Function/method returning bool
            ['Redundant comparison: bool expression === true is always resolvable without explicit comparison. Use the bool expression directly.', 33],
            ['Redundant comparison: bool expression == false is always resolvable without explicit comparison. Use the bool expression directly.', 34],
            ['Redundant comparison: bool expression !== true is always resolvable without explicit comparison. Use the bool expression directly.', 35],
            ['Redundant comparison: bool expression != false is always resolvable without explicit comparison. Use the bool expression directly.', 36],
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
