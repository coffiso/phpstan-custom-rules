<?php

declare(strict_types=1);

namespace ForbidCustomTypesRuleTest;

// ========================================
// 禁止する型の定義
// ========================================

class ForbiddenClass
{
}

interface ForbiddenInterface
{
}

trait ForbiddenTrait
{
}

class TypeHintOnlyForbiddenClass
{
}

class ForbiddenBaseClass
{
}

class SubclassOfForbiddenBase extends ForbiddenBaseClass
{
}

// ========================================
// テストケース: 基本的な検出パターン
// ========================================

class TestClass
{
    // プロパティの型チェック（ネイティブ型ヒント）- error on line 27
    private ForbiddenClass $forbiddenProperty;

    // PHPDoc @var のチェック - error on line 30
    /** @var ForbiddenClass */
    private $forbiddenVarProperty;

    // 引数の型チェック - error on line 35
    public function methodWithForbiddenParam(ForbiddenClass $forbidden): void
    {
    }

    // 戻り値の型チェック - error on line 40
    public function methodWithForbiddenReturn(): ForbiddenClass
    {
        return new ForbiddenClass();
    }
}

// ========================================
// テストケース: extends のチェック
// ========================================

// extends のチェック - error on line 49
class ClassExtendingForbidden extends ForbiddenClass
{
}

// implements のチェック - error on line 53
class ClassImplementingForbidden implements ForbiddenInterface
{
}

// trait use のチェック - error on line 59
class ClassUsingForbiddenTrait
{
    use ForbiddenTrait;
}

// ========================================
// テストケース: PHPDoc での検出
// ========================================

class TestClass2
{
    // PHPDoc @param のチェック - error on line 66
    /**
     * @param ForbiddenClass $forbidden
     */
    public function methodWithForbiddenPhpDocParam($forbidden): void
    {
    }

    // PHPDoc @return のチェック - error on line 75
    /**
     * @return ForbiddenClass
     */
    public function methodWithForbiddenPhpDocReturn()
    {
        return new ForbiddenClass();
    }

    // Union型での禁止型チェック - error on line 83
    public function methodWithUnionParam(ForbiddenClass|string $param): void
    {
    }

    // Nullable型での禁止型チェック - error on line 88
    public function methodWithNullableParam(?ForbiddenClass $param): void
    {
    }
}

// ========================================
// テストケース: typeHintOnly オプション
// ========================================

// typeHintOnly: extends は検出されない（型ヒントのみ禁止なので）
class ClassExtendingTypeHintOnlyForbidden extends TypeHintOnlyForbiddenClass
{
}

class TestClass3
{
    // typeHintOnly: 引数での使用は検出される - error on line 102
    public function methodWithTypeHintOnlyForbidden(TypeHintOnlyForbiddenClass $param): void
    {
    }

    // withSubclasses: サブクラスの使用も検出 - error on line 107
    public function methodWithSubclassOfForbidden(SubclassOfForbiddenBase $param): void
    {
    }
}

// extends でサブクラスチェック - error on line 111
class ClassExtendingForbiddenBase extends ForbiddenBaseClass
{
}

// ========================================
// テストケース: 検出されないべきケース
// ========================================

// 禁止されていない型の使用
class AllowedClass
{
}

class TestClassWithAllowedTypes
{
    private AllowedClass $allowedProperty;

    public function methodWithAllowedParam(AllowedClass $param): void
    {
    }

    public function methodWithAllowedReturn(): AllowedClass
    {
        return new AllowedClass();
    }
}

// 組み込み型の使用
class TestClassWithBuiltinTypes
{
    private string $stringProperty;
    private int $intProperty;
    private ?string $nullableStringProperty;

    public function methodWithBuiltinParam(string $param, int $count): void
    {
    }

    public function methodWithBuiltinReturn(): string
    {
        return 'test';
    }
}

// typeHintOnlyがtrueの場合、extendsは検出されない
// （上のClassExtendingTypeHintOnlyForbiddenで確認済み）

// withSubclassesがfalseの場合、サブクラスは検出されない
// ForbiddenClass のサブクラスを作成しても、withSubclasses=falseなので検出されない
class SubclassOfForbidden extends ForbiddenClass
{
}

class TestClassWithSubclass
{
    // ForbiddenClass は withSubclasses=false なので、サブクラスは検出されない
    public function methodWithSubclassParam(SubclassOfForbidden $param): void
    {
    }
}
