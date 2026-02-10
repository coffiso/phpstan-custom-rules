<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\Helper\ClassFunctionCandidateInspector;
use Coffiso\PHPStan\Rule\SuggestFunctionInsteadOfClassStaticCallRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SuggestFunctionInsteadOfClassStaticCallRule>
 */
final class SuggestFunctionInsteadOfClassStaticCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $inspector = new ClassFunctionCandidateInspector(
            reportStaticOnlyClasses: true,
            reportSinglePublicMethodClasses: true,
            ignoreConstructorWithArguments: true,
        );

        return new SuggestFunctionInsteadOfClassStaticCallRule(
            inspector: $inspector,
            reflectionProvider: self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/SuggestFunctionCallSiteTest.php'], [
            [
                'Call to SuggestFunctionCallSiteTest\StaticOnlyUtils::format() — class SuggestFunctionCallSiteTest\StaticOnlyUtils contains only public static methods and can be replaced with functions.',
                148,
            ],
            [
                'Call to SuggestFunctionCallSiteTest\StaticOnlyUtils::parse() — class SuggestFunctionCallSiteTest\StaticOnlyUtils contains only public static methods and can be replaced with functions.',
                149,
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
