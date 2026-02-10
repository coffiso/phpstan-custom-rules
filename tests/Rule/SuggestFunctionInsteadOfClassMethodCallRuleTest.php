<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\Helper\ClassFunctionCandidateInspector;
use Coffiso\PHPStan\Rule\SuggestFunctionInsteadOfClassMethodCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SuggestFunctionInsteadOfClassMethodCallRule>
 */
final class SuggestFunctionInsteadOfClassMethodCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $inspector = new ClassFunctionCandidateInspector(
            reportStaticOnlyClasses: true,
            reportSinglePublicMethodClasses: true,
            ignoreConstructorWithArguments: true,
        );

        return new SuggestFunctionInsteadOfClassMethodCallRule(
            inspector: $inspector,
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/SuggestFunctionCallSiteTest.php'], [
            [
                'Call to SuggestFunctionCallSiteTest\SingleMethodRunner::run() — class SuggestFunctionCallSiteTest\SingleMethodRunner has a single public method (run) and can be replaced with a function.',
                153,
            ],
            [
                'Call to SuggestFunctionCallSiteTest\SingleMethodWithHelper::execute() — class SuggestFunctionCallSiteTest\SingleMethodWithHelper has a single public method (execute) and can be replaced with a function.',
                157,
            ],
        ]);
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../rules.neon',
            __DIR__ . '/suggestFunctionCallSiteTest.neon',
        ];
    }
}
