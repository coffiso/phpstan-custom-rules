<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\SuggestFunctionInsteadOfClassRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<SuggestFunctionInsteadOfClassRule>
            [
                'Class SuggestFunctionInsteadOfClassRuleTest\\SinglePublicMethodClass has a single public method (run) and can be replaced with a function.',
                18,
            ],
            [
                'Class SuggestFunctionInsteadOfClassRuleTest\\SinglePublicMethodWithPrivateHelper has a single public method (run) and can be replaced with a function.',
                25,
            ],
            ignoreConstructorWithArguments: true,
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/SuggestFunctionInsteadOfClassRuleTest.php'], [
            [
                'Class SuggestFunctionInsteadOfClassRuleTest\StaticOnlyClass contains only public static methods and can be replaced with functions.',
                7,
            ],
            [
                'Class SuggestFunctionInsteadOfClassRuleTest\SinglePublicMethodClass has a single public method (run) and can be replaced with a function.',
                [
                    'Class SuggestFunctionInsteadOfClassRuleTest\\WithConstructorNoArgs has a single public method (run) and can be replaced with a function.',
                    107,
                ],
                [
                    'Class SuggestFunctionInsteadOfClassRuleTest\\PublicStaticOnlyWithPrivateMethod has a single public method (foo) and can be replaced with a function.',
                    118,
                ],
                91,
            ],
            [
                'Class SuggestFunctionInsteadOfClassRuleTest\PublicStaticOnlyWithPrivateMethod has a single public method (foo) and can be replaced with a function.',
                102,
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
