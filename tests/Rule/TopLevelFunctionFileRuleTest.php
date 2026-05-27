<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\TopLevelFunctionFileRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TopLevelFunctionFileRule>
 */
final class TopLevelFunctionFileRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TopLevelFunctionFileRule();
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/data/validFunction.php',
            __DIR__ . '/data/noNamespace.php',
            __DIR__ . '/data/multipleFunctions.php',
            __DIR__ . '/data/nameMismatch.php',
            __DIR__ . '/data/notLowerCamel.php',
            __DIR__ . '/data/extraTopLevel.php',
            __DIR__ . '/data/NotLowerCamelFile.php',
        ], [
            [
                'Function file name must be lowerCamelCase.',
                1,
            ],
            [
                'Functions in a function file must belong to a namespace.',
                5,
            ],
            [
                'The function file name and function name must be the same.',
                7,
            ],
            [
                'The function file name and function name must be the same.',
                7,
            ],
            [
                'Only one function in a function file is allowed.',
                11,
            ],
            [
                'class is not allowed in a function file.',
                11,
            ],
        ]);
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../rules.neon'];
    }
}
