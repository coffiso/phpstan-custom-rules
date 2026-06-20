<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\TopLevelFunctionFileRule;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\MockObject\MockObject;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
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

    public function testTracksFunctionCountPerFileWhenFilesAreInterleaved(): void
    {
        $rule = new TopLevelFunctionFileRule();
        $fooScope = $this->createScope('/tmp/foo.php', 'TopLevelFunctionFileRuleTest');
        $barScope = $this->createScope('/tmp/bar.php', 'TopLevelFunctionFileRuleTest');

        self::assertSame([], $rule->processNode(new Function_('foo'), $fooScope));
        self::assertSame([], $rule->processNode(new Function_('bar'), $barScope));

        $errors = $rule->processNode(new Function_('foo'), $fooScope);

        self::assertSame(
            ['Only one function in a function file is allowed.'],
            array_map(static fn ($error): string => $error->getMessage(), $errors),
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../rules.neon'];
    }

    private function createScope(string $file, string $namespace): Scope&NodeCallbackInvoker
    {
        /** @var MockObject&Scope&NodeCallbackInvoker $scope */
        $scope = $this->createMockForIntersectionOfInterfaces([Scope::class, NodeCallbackInvoker::class]);
        $scope->method('getFile')->willReturn($file);
        $scope->method('getNamespace')->willReturn($namespace);

        return $scope;
    }
}
