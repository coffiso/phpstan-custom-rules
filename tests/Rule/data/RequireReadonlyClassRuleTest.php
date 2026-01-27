<?php

declare(strict_types=1);

namespace RequireReadonlyClassRuleTest;

/**
 * すべてのプロパティがreadonly → readonly classにすべき（エラー）
 */
class AllReadonlyPropertiesClass
{
    public readonly string $name;
    public readonly int $age;

    public function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age = $age;
    }
}

/**
 * プロモートプロパティがすべてreadonly → readonly classにすべき（エラー）
 */
class AllReadonlyPromotedPropertiesClass
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {
    }
}

/**
 * 通常プロパティとプロモートプロパティの混合、すべてreadonly → readonly classにすべき（エラー）
 */
class MixedReadonlyPropertiesClass
{
    public readonly string $name;

    public function __construct(
        string $name,
        public readonly int $age,
        public readonly bool $active,
    ) {
        $this->name = $name;
    }
}

/**
 * staticプロパティを持つ → readonly classにできない（エラーなし）
 */
class HasStaticPropertyClass
{
    public readonly string $name;
    public static int $count = 0;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/**
 * 型宣言なしのプロパティを持つ → readonly classにできない（エラーなし）
 */
class HasUntypedPropertyClass
{
    public readonly string $name;
    /** @var mixed */
    public $data;

    public function __construct(string $name, mixed $data)
    {
        $this->name = $name;
        $this->data = $data;
    }
}

/**
 * abstractクラスですべてreadonly → エラー（オプションで制御可能）
 */
abstract class AbstractAllReadonlyClass
{
    public readonly string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/**
 * finalクラスですべてreadonly → エラー
 */
final class FinalAllReadonlyClass
{
    public readonly string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/**
 * 非readonlyプロパティがある → readonly classにできない（エラーなし）
 */
class HasNonReadonlyPropertyClass
{
    public readonly string $name;
    public int $count;

    public function __construct(string $name, int $count)
    {
        $this->name = $name;
        $this->count = $count;
    }
}

/**
 * すでにreadonly class → エラーなし
 */
readonly class AlreadyReadonlyClass
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/**
 * プロパティを持たないクラス → エラーなし
 */
class NoPropertiesClass
{
    public function doSomething(): void
    {
    }
}

/**
 * readonly classを継承 → エラーなし（子クラスも自動的にreadonly）
 */
readonly class ParentReadonlyClass
{
    public function __construct(
        public string $name,
    ) {
    }
}

readonly class ChildOfReadonlyClass extends ParentReadonlyClass
{
    public function __construct(
        string $name,
        public int $age,
    ) {
        parent::__construct($name);
    }
}

/**
 * 非readonlyクラスを継承し、すべてのプロパティがreadonly
 * → reportClassesExtendingNonReadonlyParent がfalseの場合はエラーなし
 */
class NonReadonlyParent
{
    public function __construct(
        public string $value,
    ) {
    }
}

class ChildWithReadonlyProperty extends NonReadonlyParent
{
    public function __construct(
        string $value,
        public readonly int $count,
    ) {
        parent::__construct($value);
    }
}
