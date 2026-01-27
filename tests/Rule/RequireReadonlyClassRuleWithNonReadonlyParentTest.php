<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\RequireReadonlyClassRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * RequireReadonlyClassRuleのテスト - reportClassesExtendingNonReadonlyParent:true設定
 *
 * @extends RuleTestCase<RequireReadonlyClassRule>
 */
final class RequireReadonlyClassRuleWithNonReadonlyParentTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RequireReadonlyClassRule(
            reportAbstractClasses: true,
            reportClassesExtendingNonReadonlyParent: true,
        );
    }

    public function testNonReadonlyParentClassesReported(): void
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
            // ChildWithReadonlyProperty extends NonReadonlyParent - 報告される
            [
                'Class RequireReadonlyClassRuleTest\ChildWithReadonlyProperty extends non-readonly class RequireReadonlyClassRuleTest\NonReadonlyParent and cannot be made readonly.',
                177,
            ],
            [
                'Class RequireReadonlyClassRuleTest\SingleReadonlyPropertyClass has all properties (1) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                199,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassUsingReadonlyTrait has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                217,
            ],
            [
                'Class RequireReadonlyClassRuleTest\Person has all properties (3) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                275,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithNullableProperty has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                297,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithUnionType has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                312,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithComplexTypes has all properties (3) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                327,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithMultipleTraits has all properties (3) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                354,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithPrivateReadonlyProperty has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                372,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithProtectedReadonlyProperty has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                387,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithPromotedOnlyProperties has all properties (3) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                403,
            ],
            [
                'Class RequireReadonlyClassRuleTest\ClassWithReadonlyPromotedWithDefault has all properties (2) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                416,
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
        ];
    }
}
