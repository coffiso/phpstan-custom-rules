<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * 関数に置き換えるべきクラスの使用を検出するルール。
 *
 * @implements Rule<Node>
 */
final readonly class SuggestFunctionInsteadOfClassUsageRule implements Rule
{
    public function __construct(
        private bool $reportStaticOnlyClasses = true,
        private bool $reportSinglePublicMethodClasses = true,
        private bool $ignoreConstructorWithArguments = true,
    ) {
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param Node $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // 静的メソッド呼び出しを検知
        if ($node instanceof StaticCall) {
            return $this->processStaticCall($node, $scope);
        }

        // インスタンスメソッド呼び出しを検知
        if ($node instanceof MethodCall) {
            return $this->processMethodCall($node, $scope);
        }

        // __invoke呼び出し（関数のような呼び出し）を検知
        if ($node instanceof FuncCall) {
            return $this->processFuncCall($node, $scope);
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processStaticCall(StaticCall $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        $className = $scope->resolveName($node->class);
        if (!$scope->hasClass($className)) {
            return [];
        }

        $classReflection = $scope->getClassReflection($className);
        if ($classReflection === null) {
            return [];
        }

        $checkResult = $this->shouldSuggestFunction($classReflection);
        if ($checkResult === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Class %s %s Consider using a function instead of a static method call.',
                $classReflection->getName(),
                $checkResult,
            ))
                ->identifier('coffiso.suggestFunctionInsteadOfClass.staticCallUsage')
                ->line($node->getLine())
                ->build(),
        ];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processMethodCall(MethodCall $node, Scope $scope): array
    {
        $callerType = $scope->getType($node->var);

        $classReflections = $callerType->getObjectClassReflections();
        foreach ($classReflections as $classReflection) {
            $checkResult = $this->shouldSuggestFunction($classReflection);
            if ($checkResult === null) {
                continue;
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s %s Consider using a function instead of an instance method call.',
                    $classReflection->getName(),
                    $checkResult,
                ))
                    ->identifier('coffiso.suggestFunctionInsteadOfClass.methodCallUsage')
                    ->line($node->getLine())
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processFuncCall(FuncCall $node, Scope $scope): array
    {
        // __invokeマジックメソッドの省略呼び出しを検知
        // 例: (new MyClass())() or $obj()
        if (!$node->name instanceof Node\Expr) {
            return [];
        }

        $callerType = $scope->getType($node->name);

        $classReflections = $callerType->getObjectClassReflections();
        foreach ($classReflections as $classReflection) {
            // __invokeメソッドを持つクラスのみ対象
            if (!$classReflection->hasMethod('__invoke')) {
                continue;
            }

            $checkResult = $this->shouldSuggestFunction($classReflection);
            if ($checkResult === null) {
                continue;
            }

            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s %s Consider using a function instead of __invoke magic method.',
                    $classReflection->getName(),
                    $checkResult,
                ))
                    ->identifier('coffiso.suggestFunctionInsteadOfClass.invokeUsage')
                    ->line($node->getLine())
                    ->build(),
            ];
        }

        return [];
    }

    /**
     * クラスが関数に置き換えるべきかをチェック
     *
     * @return string|null 置き換えを推奨する理由、または null（推奨しない場合）
     */
    private function shouldSuggestFunction(ClassReflection $classReflection): ?string
    {
        // 匿名クラスは対象外
        if ($classReflection->isAnonymous()) {
            return null;
        }

        // 継承またはインターフェース実装がある場合は対象外
        if ($classReflection->getParentClass() !== null || $classReflection->getInterfaces() !== []) {
            return null;
        }

        // トレイトを使用している場合は対象外
        if ($classReflection->getTraits() !== []) {
            return null;
        }

        // publicプロパティがある場合は対象外
        foreach ($classReflection->getProperties() as $property) {
            if ($property->isPublic()) {
                return null;
            }
        }

        // staticプロパティがある場合は対象外
        foreach ($classReflection->getProperties() as $property) {
            if ($property->isStatic()) {
                return null;
            }
        }

        // コンストラクタに引数がある場合は対象外（設定による）
        if ($this->ignoreConstructorWithArguments && $classReflection->hasConstructor()) {
            $constructor = $classReflection->getConstructor();
            $variants = $constructor->getVariants();
            foreach ($variants as $variant) {
                if (count($variant->getParameters()) > 0) {
                    return null;
                }
            }
        }

        $publicMethods = [];
        $allMethodsPublicStatic = true;
        $hasAnyMethod = false;

        foreach ($classReflection->getMethods() as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }

            $hasAnyMethod = true;

            if (!($method->isPublic() && $method->isStatic())) {
                $allMethodsPublicStatic = false;
            }

            if ($method->isPublic()) {
                $publicMethods[] = $method->getName();
            }
        }

        if (count($publicMethods) === 0) {
            return null;
        }

        // 静的メソッドのみのクラス
        if ($this->reportStaticOnlyClasses && $hasAnyMethod && $allMethodsPublicStatic) {
            return 'contains only public static methods and can be replaced with functions.';
        }

        // 単一のpublicメソッドを持つクラス
        if ($this->reportSinglePublicMethodClasses && count($publicMethods) === 1) {
            return sprintf('has a single public method (%s) and can be replaced with a function.', $publicMethods[0]);
        }

        return null;
    }
}
