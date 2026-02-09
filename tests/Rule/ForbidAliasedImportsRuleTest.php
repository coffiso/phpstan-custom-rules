<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\ForbidAliasedImportsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidAliasedImportsRule>
 */
final class ForbidAliasedImportsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidAliasedImportsRule(
            allowSameNameAlias: false,
            reportGroupUse: true,
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/ForbidAliasedImportsRuleTest.php'], [
            [
                'Aliased import "use ForbidAliasedImportsRuleTest\\Vendor\\Bar as Baz" is forbidden. Prefer importing the specific symbol without alias (use ForbidAliasedImportsRuleTest\\Vendor\\Bar).',
                25,
            ],
            [
                'Aliased import "use ForbidAliasedImportsRuleTest\\Vendor\\Corge as Corge" is forbidden. Prefer importing the specific symbol without alias (use ForbidAliasedImportsRuleTest\\Vendor\\Corge).',
                27,
            ],
            [
                'Aliased import "use ForbidAliasedImportsRuleTest\\Vendor\\Baz as BazAlias" is forbidden. Prefer importing the specific symbol without alias (use ForbidAliasedImportsRuleTest\\Vendor\\Baz).',
                28,
            ],
            [
                'Aliased import "use ForbidAliasedImportsRuleTest\\Vendor\\Bar as BarAlias" is forbidden. Prefer importing the specific symbol without alias (use ForbidAliasedImportsRuleTest\\Vendor\\Bar).',
                29,
            ],
            [
                'Aliased import "use ForbidAliasedImportsRuleTest\\Vendor\\Corge as Corge" is forbidden. Prefer importing the specific symbol without alias (use ForbidAliasedImportsRuleTest\\Vendor\\Corge).',
                29,
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
