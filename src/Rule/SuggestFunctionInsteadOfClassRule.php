<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * クラスである必要のないケースを検出し、関数定義への置き換えを推奨するルール。
 *
 * @implements Rule<InClassNode>
 */
final readonly class SuggestFunctionInsteadOfClassRule implements Rule
{
    public function __construct(
        private bool $reportStaticOnlyClasses = true,
        private bool $reportSinglePublicMethodClasses = true,
        private bool $ignoreConstructorWithArguments = true,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classNode = $node->getOriginalNode();
        $classReflection = $node->getClassReflection();

        if (!$classNode instanceof Class_) {
            return [];
        }

        if ($classReflection->isAnonymous()) {
            return [];
        }

        if ($classNode->extends !== null || $classNode->implements !== []) {
            return [];
        }

        if ($this->hasTraitUse($classNode)) {
            return [];
        }

        if ($this->hasPublicProperty($classNode)) {
            return [];
        }

        if ($this->hasStaticProperty($classNode)) {
            return [];
        }

        if ($this->ignoreConstructorWithArguments && $this->hasConstructorWithArguments($classNode)) {
            return [];
        }

        $methodInfo = $this->collectMethodInfo($classNode);

        if ($methodInfo->publicMethodCount === 0) {
            return [];
        }

        if ($this->reportStaticOnlyClasses && $methodInfo->hasAnyMethod && $methodInfo->allMethodsPublicStatic) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s contains only public static methods and can be replaced with functions.',
                    $classReflection->getName(),
                ))
                    ->identifier('coffiso.suggestFunctionInsteadOfClass.staticOnly')
                    ->line($classNode->getLine())
                    ->build(),
            ];
        }

        if ($this->reportSinglePublicMethodClasses && $methodInfo->publicMethodCount === 1) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s has a single public method (%s) and can be replaced with a function.',
                    $classReflection->getName(),
                    $methodInfo->singlePublicMethodName,
                ))
                    ->identifier('coffiso.suggestFunctionInsteadOfClass.singlePublicMethod')
                    ->line($classNode->getLine())
                    ->build(),
            ];
        }

        return [];
    }

    private function hasTraitUse(Class_ $classNode): bool
    {
        foreach ($classNode->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                return true;
            }
        }

        return false;
    }

    private function hasPublicProperty(Class_ $classNode): bool
    {
        foreach ($classNode->getProperties() as $property) {
            if ($property->isPublic()) {
                return true;
            }
        }

        return false;
    }

    private function hasStaticProperty(Class_ $classNode): bool
    {
        foreach ($classNode->getProperties() as $property) {
            if ($property->isStatic()) {
                return true;
            }
        }

        return false;
    }

    private function hasConstructorWithArguments(Class_ $classNode): bool
    {
        $constructor = $classNode->getMethod('__construct');
        if ($constructor === null) {
            return false;
        }

        return $constructor->params !== [];
    }

    private function collectMethodInfo(Class_ $classNode): MethodInfo
    {
        $publicMethodCount = 0;
        $singlePublicMethodName = '';
        $hasAnyMethod = false;
        $allMethodsPublicStatic = true;

        foreach ($classNode->getMethods() as $method) {
            $hasAnyMethod = true;

            if (!($method->isPublic() && $method->isStatic())) {
                $allMethodsPublicStatic = false;
            }

            $methodName = $method->name->toString();
            if ($methodName === '__construct') {
                continue;
            }

            if ($method->isPublic()) {
                $publicMethodCount++;
                $singlePublicMethodName = $methodName;
            }
        }

        return new MethodInfo(
            publicMethodCount: $publicMethodCount,
            singlePublicMethodName: $singlePublicMethodName,
            hasAnyMethod: $hasAnyMethod,
            allMethodsPublicStatic: $allMethodsPublicStatic,
        );
    }
}

final readonly class MethodInfo
{
    public function __construct(
        public int $publicMethodCount,
        public string $singlePublicMethodName,
        public bool $hasAnyMethod,
        public bool $allMethodsPublicStatic,
    ) {
    }
}
