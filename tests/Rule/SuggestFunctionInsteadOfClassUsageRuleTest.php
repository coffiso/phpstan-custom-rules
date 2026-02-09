<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\SuggestFunctionInsteadOfClassUsageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SuggestFunctionInsteadOfClassUsageRule>
 */
final class SuggestFunctionInsteadOfClassUsageRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new SuggestFunctionInsteadOfClassUsageRule(
            reportStaticOnlyClasses: true,
            reportSinglePublicMethodClasses: true,
            ignoreConstructorWithArguments: true,
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/SuggestFunctionInsteadOfClassUsageRuleTest.php'], [
            // 静的メソッド呼び出し
            [
                'Class SuggestFunctionInsteadOfClassUsageRuleTest\StaticOnlyClass contains only public static methods and can be replaced with functions. Consider using a function instead of a static method call.',
                132,
            ],
            [
                'Class SuggestFunctionInsteadOfClassUsageRuleTest\SinglePublicMethodClass contains only public static methods and can be replaced with functions. Consider using a function instead of a static method call.',
                135,
            ],
            // インスタンスメソッド呼び出し
            [
                'Class SuggestFunctionInsteadOfClassUsageRuleTest\SinglePublicMethodClass has a single public method (run) and can be replaced with a function. Consider using a function instead of an instance method call.',
                139,
            ],
            [
                'Class SuggestFunctionInsteadOfClassUsageRuleTest\SinglePublicMethodWithPrivateHelper has a single public method (run) and can be replaced with a function. Consider using a function instead of an instance method call.',
                142,
            ],
            // __invoke呼び出し
            [
                'Class SuggestFunctionInsteadOfClassUsageRuleTest\InvokableClass has a single public method (__invoke) and can be replaced with a function. Consider using a function instead of __invoke magic method.',
                146,
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
