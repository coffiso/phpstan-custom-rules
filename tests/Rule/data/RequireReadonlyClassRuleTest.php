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
/**
 * プロパティなしのクラス（readonlyにするプロパティがない）
 */
class EmptyClass
{
    public function doSomething(): void
    {
    }
}

/**
 * プロパティは1つだけで、readonlyである
 */
class SingleReadonlyPropertyClass
{
    public readonly string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/**
 * トレイトを使用し、トレイトのプロパティがすべてreadonly
 */
trait ReadonlyPropertyTrait
{
    public readonly string $trait_prop;
}

class ClassUsingReadonlyTrait
{
    use ReadonlyPropertyTrait;

    public readonly int $class_prop;

    public function __construct(string $trait_prop, int $class_prop)
    {
        $this->trait_prop = $trait_prop;
        $this->class_prop = $class_prop;
    }
}

/**
 * トレイトを使用し、トレイトのプロパティにuntyped のものがある
 */
trait UntypedPropertyTrait
{
    /** @var string */
    public $untyped_trait_prop;
}

class ClassUsingUntypedTrait
{
    use UntypedPropertyTrait;

    public readonly int $class_prop;

    public function __construct(string $untyped_trait_prop, int $class_prop)
    {
        $this->untyped_trait_prop = $untyped_trait_prop;
        $this->class_prop = $class_prop;
    }
}

/**
 * トレイトを使用し、トレイトのプロパティがstaticである
 */
trait StaticPropertyTrait
{
    public static string $static_trait_prop = 'static';
}

class ClassUsingStaticTrait
{
    use StaticPropertyTrait;

    public readonly int $class_prop;

    public function __construct(int $class_prop)
    {
        $this->class_prop = $class_prop;
    }
}

/**
 * イミュータブルなクラスの典型例
 */
class Person
{
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly int $age;

    public function __construct(string $firstName, string $lastName, int $age)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->age = $age;
    }

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}

/**
 * nullableなプロパティがある（型宣言あり）
 */
class ClassWithNullableProperty
{
    public readonly string $name;
    public readonly ?string $nickname;

    public function __construct(string $name, ?string $nickname = null)
    {
        $this->name = $name;
        $this->nickname = $nickname;
    }
}

/**
 * ユニオン型を使用したプロパティ
 */
class ClassWithUnionType
{
    public readonly string|int $identifier;
    public readonly bool $active;

    public function __construct(string|int $identifier, bool $active = true)
    {
        $this->identifier = $identifier;
        $this->active = $active;
    }
}

/**
 * モダンな複雑な型宣言（PHP 8.1+）
 */
class ClassWithComplexTypes
{
    public readonly array $items;
    public readonly \DateTimeInterface $createdAt;
    public readonly bool $archived;

    public function __construct(array $items, \DateTimeInterface $createdAt)
    {
        $this->items = $items;
        $this->createdAt = $createdAt;
        $this->archived = false;
    }
}

/**
 * 複数のトレイトを使用
 */
trait TimestampTrait
{
    public readonly \DateTime $createdAt;
}

trait IdentifierTrait
{
    public readonly string $id;
}

class ClassWithMultipleTraits
{
    use TimestampTrait;
    use IdentifierTrait;

    public readonly string $name;

    public function __construct(string $id, string $name, \DateTime $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
    }
}

/**
 * privateプロパティがあるケース
 */
class ClassWithPrivateReadonlyProperty
{
    public readonly string $public_prop;
    private readonly string $private_prop;

    public function __construct(string $public_prop, string $private_prop)
    {
        $this->public_prop = $public_prop;
        $this->private_prop = $private_prop;
    }
}

/**
 * protectedプロパティがあるケース
 */
class ClassWithProtectedReadonlyProperty
{
    public readonly string $public_prop;
    protected readonly string $protected_prop;

    public function __construct(string $public_prop, string $protected_prop)
    {
        $this->public_prop = $public_prop;
        $this->protected_prop = $protected_prop;
    }
}

/**
 * デフォルト値を持つプロパティ（いない）
 * プロモートされたプロパティのみ
 */
class ClassWithPromotedOnlyProperties
{
    public function __construct(
        public readonly string $name,
        public readonly int $count,
        public readonly bool $active = true,
    ) {
    }
}

/**
 * readonlyかつデフォルト値を持つプロモートプロパティ
 */
class ClassWithReadonlyPromotedWithDefault
{
    public function __construct(
        public readonly string $name,
        public readonly int $count = 0,
    ) {
    }
}