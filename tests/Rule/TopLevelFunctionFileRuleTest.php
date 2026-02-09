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
                'Top-level function files must declare a namespace.',
                5,
            ],
            [
                'Only one top-level function is allowed per file.',
                11,
            ],
            [
                'Function name must match file name. Expected multipleFunctions.',
                11,
            ],
            [
                'Function name must match file name. Expected nameMismatch.',
                7,
            ],
            [
                'Function name must be lowerCamelCase.',
                7,
            ],
            [
                'Function name must match file name. Expected notLowerCamel.',
                7,
            ],
            [
                'Top-level statement Stmt_Class is not allowed in a function-only file.',
                11,
            ],
            [
                'File name must be lowerCamelCase.',
                7,
            ],
            [
                'Function name must match file name. Expected NotLowerCamelFile.',
                7,
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
