<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule\Helper;

use PHPStan\Reflection\ClassReflection;
use ReflectionMethod;

/**
 * ClassReflection からクラスが「関数で置き換え可能か」を判定するヘルパー。
 *
 * コールサイト用ルール（StaticCall / MethodCall）と
 * 定義側ルール（SuggestFunctionInsteadOfClassRule）で共通利用する。
 */
final class ClassFunctionCandidateInspector
{
    /** @var array<string, ?MethodInfo> */
    private array $cache = [];

    public function __construct(
        private bool $reportStaticOnlyClasses = true,
        private bool $reportSinglePublicMethodClasses = true,
        private bool $ignoreConstructorWithArguments = true,
    ) {
    }

    /**
     * クラスが「関数で置き換え可能」な候補かどうかを判定し、MethodInfo を返す。
     * 候補でない場合は null を返す。
     */
    public function inspect(ClassReflection $classReflection): ?MethodInfo
    {
        $className = $classReflection->getName();

        if (array_key_exists($className, $this->cache)) {
            return $this->cache[$className];
        }

        $result = $this->doInspect($classReflection);
        $this->cache[$className] = $result;

        return $result;
    }

    /**
     * staticOnly / singlePublicMethod の条件に一致するかを判定して返す。
     * いずれにも該当しなければ null。
     */
    private function doInspect(ClassReflection $classReflection): ?MethodInfo
    {
        if ($classReflection->isAnonymous()) {
            return null;
        }

        if ($classReflection->isInterface() || $classReflection->isTrait() || $classReflection->isEnum()) {
            return null;
        }

        $parentClass = $classReflection->getParentClass();
        if ($parentClass !== null && $parentClass->getName() !== 'stdClass') {
            return null;
        }

        if ($classReflection->getInterfaces() !== []) {
            return null;
        }

        if ($classReflection->getTraits() !== []) {
            return null;
        }

        $nativeReflection = $classReflection->getNativeReflection();

        // publicプロパティまたはstaticプロパティがあればスキップ
        foreach ($nativeReflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }
            if ($property->isPublic()) {
                return null;
            }
            if ($property->isStatic()) {
                return null;
            }
        }

        // コンストラクタ引数チェック
        if ($this->ignoreConstructorWithArguments) {
            $constructor = $nativeReflection->getConstructor();
            if ($constructor !== null
                && $constructor->getDeclaringClass()->getName() === $classReflection->getName()
                && $constructor->getNumberOfParameters() > 0
            ) {
                return null;
            }
        }

        // メソッド情報を収集
        $publicMethodCount = 0;
        $singlePublicMethodName = '';
        $hasAnyMethod = false;
        $allMethodsPublicStatic = true;

        foreach ($nativeReflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }

            $hasAnyMethod = true;

            if (!($method->isPublic() && $method->isStatic())) {
                $allMethodsPublicStatic = false;
            }

            $methodName = $method->getName();
            if ($methodName === '__construct') {
                continue;
            }

            if ($method->isPublic()) {
                $publicMethodCount++;
                $singlePublicMethodName = $methodName;
            }
        }

        if ($publicMethodCount === 0) {
            return null;
        }

        // __invoke() のみが public メソッドの場合は callable として機能するため除外
        if ($publicMethodCount === 1 && $singlePublicMethodName === '__invoke') {
            return null;
        }

        $methodInfo = new MethodInfo(
            publicMethodCount: $publicMethodCount,
            singlePublicMethodName: $singlePublicMethodName,
            hasAnyMethod: $hasAnyMethod,
            allMethodsPublicStatic: $allMethodsPublicStatic,
        );

        // 条件に一致するか最終チェック
        if ($this->reportStaticOnlyClasses && $methodInfo->hasAnyMethod && $methodInfo->allMethodsPublicStatic) {
            return $methodInfo;
        }

        if ($this->reportSinglePublicMethodClasses && $methodInfo->publicMethodCount === 1) {
            return $methodInfo;
        }

        return null;
    }

    /**
     * 指定のMethodInfoが「staticOnlyクラス」パターンに該当するか。
     */
    public function isStaticOnlyCandidate(MethodInfo $methodInfo): bool
    {
        return $this->reportStaticOnlyClasses && $methodInfo->hasAnyMethod && $methodInfo->allMethodsPublicStatic;
    }

    /**
     * 指定のMethodInfoが「singlePublicMethodクラス」パターンに該当するか。
     */
    public function isSinglePublicMethodCandidate(MethodInfo $methodInfo): bool
    {
        return $this->reportSinglePublicMethodClasses && $methodInfo->publicMethodCount === 1;
    }
}
