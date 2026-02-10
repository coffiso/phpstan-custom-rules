<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use Coffiso\PHPStan\Rule\Helper\ClassFunctionCandidateInspector;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * 静的メソッド呼び出し（Foo::bar()）のコールサイトで、
 * 呼び先クラスが関数に置き換え可能な場合に警告するルール。
 *
 * @implements Rule<StaticCall>
 */
final readonly class SuggestFunctionInsteadOfClassStaticCallRule implements Rule
{
    public function __construct(
        private ClassFunctionCandidateInspector $inspector,
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param StaticCall $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (!$node->class instanceof Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);

        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        $methodInfo = $this->inspector->inspect($classReflection);

        if ($methodInfo === null) {
            return [];
        }

        $methodName = $node->name->toString();
        $errors = [];

        if ($this->inspector->isStaticOnlyCandidate($methodInfo)) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Call to %s::%s() — class %s contains only public static methods and can be replaced with functions.',
                $classReflection->getName(),
                $methodName,
                $classReflection->getName(),
            ))
                ->identifier('coffiso.suggestFunctionInsteadOfClass.callsite.staticOnly')
                ->build();
        } elseif ($this->inspector->isSinglePublicMethodCandidate($methodInfo)) {
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Call to %s::%s() — class %s has a single public method (%s) and can be replaced with a function.',
                $classReflection->getName(),
                $methodName,
                $classReflection->getName(),
                $methodInfo->singlePublicMethodName,
            ))
                ->identifier('coffiso.suggestFunctionInsteadOfClass.callsite.singlePublicMethod')
                ->build();
        }

        return $errors;
    }
}
