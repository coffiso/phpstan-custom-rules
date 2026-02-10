<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use Coffiso\PHPStan\Rule\Helper\ClassFunctionCandidateInspector;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * インスタンスメソッド呼び出し（$obj->bar()）のコールサイトで、
 * 呼び先クラスが関数に置き換え可能な場合に警告するルール。
 *
 * @implements Rule<MethodCall>
 */
final readonly class SuggestFunctionInsteadOfClassMethodCallRule implements Rule
{
    public function __construct(
        private ClassFunctionCandidateInspector $inspector,
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        $calledOnType = $scope->getType($node->var);
        $classReflections = $calledOnType->getObjectClassReflections();

        if ($classReflections === []) {
            return [];
        }

        // クラス内部からの自己呼び出し（$this->method()）はスキップ
        $enclosingClass = $scope->isInClass() ? $scope->getClassReflection() : null;

        $methodName = $node->name->toString();
        $errors = [];

        foreach ($classReflections as $classReflection) {
            // 自クラスへの呼び出しはスキップ
            if ($enclosingClass !== null && $enclosingClass->getName() === $classReflection->getName()) {
                continue;
            }

            $methodInfo = $this->inspector->inspect($classReflection);

            if ($methodInfo === null) {
                continue;
            }

            // staticOnly クラスへのインスタンスメソッド呼び出しも警告
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
        }

        return $errors;
    }
}
