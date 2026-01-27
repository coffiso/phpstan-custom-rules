<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * PHPStanの型解析機能を利用して、readonly classとして定義できるクラスを検出するルール。
 *
 * readonly classの条件:
 * 1. すべてのインスタンスプロパティがreadonlyである
 * 2. staticプロパティを持たない（readonly classではstaticプロパティを持てない）
 * 3. すべてのプロパティに型宣言がある（readonly classでは型宣言が必須）
 * 4. 親クラスがある場合、親クラスもreadonlyである必要がある
 * 5. トレイトで定義されたプロパティもreadonlyである必要がある
 *
 * @implements Rule<InClassNode>
 */
final readonly class RequireReadonlyClassRule implements Rule
{
    public function __construct(
        private bool $reportAbstractClasses = true,
        private bool $reportClassesExtendingNonReadonlyParent = false,
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
        $classReflection = $node->getClassReflection();
        $classNode = $node->getOriginalNode();

        // anonymous class, interface, trait, enumはスキップ
        if (!$classNode instanceof Class_) {
            return [];
        }

        if ($classReflection->isAnonymous()) {
            return [];
        }

        // すでにreadonly classの場合はスキップ
        if ($classReflection->isReadOnly()) {
            return [];
        }

        // abstractクラスをスキップするオプション
        if ($classReflection->isAbstract() && !$this->reportAbstractClasses) {
            return [];
        }

        // 親クラスがあり、それがreadonlyでない場合
        $parentClass = $classReflection->getParentClass();
        if ($parentClass !== null && !$parentClass->isReadOnly()) {
            if (!$this->reportClassesExtendingNonReadonlyParent) {
                return [];
            }

            // 親クラスがreadonlyでない場合、子クラスをreadonlyにできない
            return [
                RuleErrorBuilder::message(sprintf(
                    'Class %s extends non-readonly class %s and cannot be made readonly.',
                    $classReflection->getName(),
                    $parentClass->getName(),
                ))
                    ->identifier('coffiso.cannotBeReadonly.nonReadonlyParent')
                    ->build(),
            ];
        }

        // クラスの全プロパティをチェック
        $checkResult = $this->checkClassProperties($classReflection, $classNode);

        if ($checkResult->canBeReadonly) {
            $message = $this->buildErrorMessage($classReflection, $checkResult);
            return [
                RuleErrorBuilder::message($message)
                    ->identifier('coffiso.shouldBeReadonlyClass')
                    ->build(),
            ];
        }

        return [];
    }

    private function checkClassProperties(ClassReflection $classReflection, Class_ $classNode): PropertyCheckResult
    {
        $hasStaticProperty = false;
        $hasUntypedProperty = false;
        $hasNonReadonlyProperty = false;
        $allPropertiesReadonly = true;
        $propertyCount = 0;
        $readonlyPropertyCount = 0;

        // クラスで直接定義されたプロパティをチェック
        foreach ($classNode->getProperties() as $propertyNode) {
            foreach ($propertyNode->props as $prop) {
                $propertyCount++;

                // staticプロパティのチェック
                if ($propertyNode->isStatic()) {
                    $hasStaticProperty = true;
                    continue;
                }

                // 型宣言がないプロパティのチェック
                if ($propertyNode->type === null) {
                    $hasUntypedProperty = true;
                    $allPropertiesReadonly = false;
                    continue;
                }

                // readonlyかどうかのチェック
                if ($propertyNode->isReadonly()) {
                    $readonlyPropertyCount++;
                } else {
                    $hasNonReadonlyProperty = true;
                    $allPropertiesReadonly = false;
                }
            }
        }

        // コンストラクタのプロモートされたプロパティをチェック
        $constructor = $classNode->getMethod('__construct');
        if ($constructor !== null) {
            foreach ($constructor->params as $param) {
                // プロモートされたプロパティかどうか（visibility flagsがある場合）
                if ($param->flags === 0) {
                    continue;
                }

                $propertyCount++;

                // staticはプロモートされたプロパティではありえない

                // 型宣言がないプロモートされたプロパティ
                if ($param->type === null) {
                    $hasUntypedProperty = true;
                    $allPropertiesReadonly = false;
                    continue;
                }

                // readonlyかどうか
                if (($param->flags & Class_::MODIFIER_READONLY) !== 0) {
                    $readonlyPropertyCount++;
                } else {
                    $hasNonReadonlyProperty = true;
                    $allPropertiesReadonly = false;
                }
            }
        }

        // トレイトからのプロパティもチェック
        $traitCheckResult = $this->checkTraitProperties($classReflection);
        if ($traitCheckResult->hasStaticProperty) {
            $hasStaticProperty = true;
        }
        if ($traitCheckResult->hasUntypedProperty) {
            $hasUntypedProperty = true;
            $allPropertiesReadonly = false;
        }
        if ($traitCheckResult->hasNonReadonlyProperty) {
            $hasNonReadonlyProperty = true;
            $allPropertiesReadonly = false;
        }
        $propertyCount += $traitCheckResult->propertyCount;
        $readonlyPropertyCount += $traitCheckResult->readonlyPropertyCount;

        // readonly classにできる条件:
        // 1. プロパティが1つ以上ある
        // 2. staticプロパティがない
        // 3. 型なしプロパティがない
        // 4. すべてのプロパティがreadonly
        $canBeReadonly = $propertyCount > 0
            && !$hasStaticProperty
            && !$hasUntypedProperty
            && $allPropertiesReadonly;

        return new PropertyCheckResult(
            canBeReadonly: $canBeReadonly,
            hasStaticProperty: $hasStaticProperty,
            hasUntypedProperty: $hasUntypedProperty,
            hasNonReadonlyProperty: $hasNonReadonlyProperty,
            propertyCount: $propertyCount,
            readonlyPropertyCount: $readonlyPropertyCount,
        );
    }

    private function checkTraitProperties(ClassReflection $classReflection): PropertyCheckResult
    {
        $hasStaticProperty = false;
        $hasUntypedProperty = false;
        $hasNonReadonlyProperty = false;
        $propertyCount = 0;
        $readonlyPropertyCount = 0;

        foreach ($classReflection->getTraits() as $trait) {
            foreach ($trait->getNativeReflection()->getProperties() as $reflectionProperty) {
                // トレイトで定義されたプロパティのみ（継承されたものは除外）
                if ($reflectionProperty->getDeclaringClass()->getName() !== $trait->getName()) {
                    continue;
                }

                $propertyCount++;

                if ($reflectionProperty->isStatic()) {
                    $hasStaticProperty = true;
                    continue;
                }

                if (!$reflectionProperty->hasType()) {
                    $hasUntypedProperty = true;
                    continue;
                }

                if ($reflectionProperty->isReadOnly()) {
                    $readonlyPropertyCount++;
                } else {
                    $hasNonReadonlyProperty = true;
                }
            }
        }

        return new PropertyCheckResult(
            canBeReadonly: false, // トレイトのみでは判定しない
            hasStaticProperty: $hasStaticProperty,
            hasUntypedProperty: $hasUntypedProperty,
            hasNonReadonlyProperty: $hasNonReadonlyProperty,
            propertyCount: $propertyCount,
            readonlyPropertyCount: $readonlyPropertyCount,
        );
    }

    private function buildErrorMessage(ClassReflection $classReflection, PropertyCheckResult $result): string
    {
        $className = $classReflection->getName();

        if ($result->readonlyPropertyCount > 0) {
            return sprintf(
                'Class %s has all properties (%d) marked as readonly. Declare the class as readonly and remove readonly modifiers from individual properties.',
                $className,
                $result->readonlyPropertyCount,
            );
        }

        return sprintf(
            'Class %s can be declared as readonly.',
            $className,
        );
    }
}
