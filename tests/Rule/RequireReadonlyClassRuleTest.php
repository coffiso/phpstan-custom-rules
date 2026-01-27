<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\RequireReadonlyClassRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RequireReadonlyClassRule>
 */
final class RequireReadonlyClassRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RequireReadonlyClassRule(
            reportAbstractClasses: true,
            reportClassesExtendingNonReadonlyParent: false,
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/RequireReadonlyClassRuleTest.php'], [
            [
                'Class RequireReadonlyClassRuleTest\AllReadonlyPropertiesClass has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                10,
            ],
            [
                'Class RequireReadonlyClassRuleTest\AllReadonlyPromotedPropertiesClass has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                25,
            ],
            [
                'Class RequireReadonlyClassRuleTest\MixedReadonlyPropertiesClass has all properties (3) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                37,
            ],
            [
                'Class RequireReadonlyClassRuleTest\AbstractAllReadonlyClass has all properties (1) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                83,
            ],
            [
                'Class RequireReadonlyClassRuleTest\FinalAllReadonlyClass has all properties (1) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                96,
            ],
        ]);
    }

    // 別のルール設定でのテストは別テストクラスで実装する

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../rules.neon',
        ];
    }
}
