<?php

declare(strict_types=1);

namespace SuggestFunctionInsteadOfClassUsageRuleTest;

// 静的メソッドのみを持つクラス
class StaticOnlyClass
{
    public static function foo(): void
    {
    }

    public static function bar(): void
    {
    }
}

// 単一のpublicメソッドを持つクラス（静的）
class SinglePublicMethodClass
{
    public static function execute(): void
    {
    }
}

// 単一のpublicメソッドを持つクラス（非静的）
class SinglePublicMethodWithPrivateHelper
{
    public function run(): void
    {
    }

    private function helper(): void
    {
    }
}

// __invokeマジックメソッドを持つクラス
class InvokableClass
{
    public function __invoke(): void
    {
    }
}

// publicプロパティを持つクラス（対象外）
class WithPublicProperty
{
    public string $name;

    public static function foo(): void
    {
    }
}

// staticプロパティを持つクラス（対象外）
class WithStaticProperty
{
    private static int $count = 0;

    public static function foo(): void
    {
    }
}

// 継承を持つクラス（対象外）
class BaseClass
{
    public function run(): void
    {
    }
}

class WithExtends extends BaseClass
{
    public function run(): void
    {
    }
}

// インターフェースを実装するクラス（対象外）
interface SomeInterface
{
    public function run(): void;
}

class WithImplements implements SomeInterface
{
    public function run(): void
    {
    }
}

// トレイトを使用するクラス（対象外）
trait SomeTrait
{
    public function assist(): void
    {
    }
}

class WithTraitUse
{
    use SomeTrait;

    public static function foo(): void
    {
    }
}

// コンストラクタに引数を持つクラス（対象外）
class WithConstructorArgs
{
    public function __construct(private int $id)
    {
    }

    public function run(): void
    {
    }
}

// 複数のpublicメソッドを持つクラス（対象外）
class MultiplePublicMethods
{
    public function foo(): void
    {
    }

    public function bar(): void
    {
    }
}

// ========== 使用例 ==========

function testUsages(): void
{
    // 静的メソッド呼び出し - 警告すべき
    StaticOnlyClass::foo();

    // 静的メソッド呼び出し - 警告すべき
    SinglePublicMethodClass::execute();

    // インスタンスメソッド呼び出し - 警告すべき
    $obj = new SinglePublicMethodClass();
    $obj->execute();

    $helper = new SinglePublicMethodWithPrivateHelper();
    $helper->run();

    // __invoke呼び出し - 警告すべき
    $invokable = new InvokableClass();
    $invokable();

    // 対象外のクラスの使用 - 警告すべきでない
    WithPublicProperty::foo();
    WithStaticProperty::foo();
    $extends = new WithExtends();
    $extends->run();
    $impl = new WithImplements();
    $impl->run();
    WithTraitUse::foo();
    $args = new WithConstructorArgs(1);
    $args->run();
    $multi = new MultiplePublicMethods();
    $multi->foo();
}
