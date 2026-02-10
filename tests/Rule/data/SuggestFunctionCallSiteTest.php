<?php

declare(strict_types=1);

namespace SuggestFunctionCallSiteTest;

// ─── 対象クラス（関数に置き換え可能なクラス）───

/** public static メソッドのみのクラス */
class StaticOnlyUtils
{
    public static function format(string $value): string
    {
        return $value;
    }

    public static function parse(string $value): int
    {
        return (int) $value;
    }
}

/** public メソッドが1つだけのクラス */
class SingleMethodRunner
{
    public function run(): void
    {
    }
}

/** public メソッドが1つ + private ヘルパーがあるクラス */
class SingleMethodWithHelper
{
    public function execute(): string
    {
        return $this->helper();
    }

    private function helper(): string
    {
        return 'ok';
    }
}

// ─── 対象外クラス（警告しない）───

/** 継承あり */
class BaseService
{
    public function handle(): void
    {
    }
}
class ExtendedService extends BaseService
{
    public function handle(): void
    {
    }
}

/** インターフェース実装あり */
interface Runnable
{
    public function run(): void;
}
class RunnableImpl implements Runnable
{
    public function run(): void
    {
    }
}

/** トレイトを使用 */
trait HelperTrait
{
    public function assist(): void
    {
    }
}
class WithTraitClass
{
    use HelperTrait;

    public static function doSomething(): void
    {
    }
}

/** コンストラクタ引数あり */
class ServiceWithDeps
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}

/** public プロパティあり */
class WithPublicProp
{
    public string $label = '';

    public static function create(): self
    {
        return new self();
    }
}

/** static プロパティあり */
class WithStaticProp
{
    private static int $count = 0;

    public static function increment(): void
    {
        self::$count++;
    }
}

/** public メソッド2つ以上（staticOnly でない） */
class MultiMethodClass
{
    public function first(): void
    {
    }

    public function second(): void
    {
    }
}

/** __invoke() のみ（関数型クラス） */
class CallableClass
{
    public function __invoke(string $value): string
    {
        return $value;
    }
}

// ─── コールサイト（ここで警告が出る / 出ない） ───

// 1. StaticOnlyUtils への静的呼び出し → 警告あり
StaticOnlyUtils::format('hello');   // line 136
StaticOnlyUtils::parse('42');       // line 137

// 2. SingleMethodRunner のインスタンス呼び出し → 警告あり
$runner = new SingleMethodRunner();
$runner->run();                     // line 141

// 3. SingleMethodWithHelper のインスタンス呼び出し → 警告あり
$helper = new SingleMethodWithHelper();
$helper->execute();                 // line 145

// 4. 継承クラスへの呼び出し → 警告なし
$ext = new ExtendedService();
$ext->handle();

// 5. インターフェース実装クラスへの呼び出し → 警告なし
$impl = new RunnableImpl();
$impl->run();

// 6. トレイト使用クラスへの呼び出し → 警告なし
WithTraitClass::doSomething();

// 7. コンストラクタ引数ありクラスへの呼び出し → 警告なし
$svc = new ServiceWithDeps('test');
$svc->getName();

// 8. public プロパティありクラスへの呼び出し → 警告なし
WithPublicProp::create();

// 9. static プロパティありクラスへの呼び出し → 警告なし
WithStaticProp::increment();

// 10. 複数 public メソッドクラスへの呼び出し → 警告なし
$multi = new MultiMethodClass();
$multi->first();

// 11. __invoke() のみのクラス（callable）→ 警告なし
$callable = new CallableClass();
$callable('test');

// 12. 動的メソッド名 → 警告なし
