<?php

declare(strict_types=1);

namespace SuggestFunctionInsteadOfClassRuleTest;

class StaticOnlyClass
{
    public static function foo(): void
    {
    }

    public static function bar(): void
    {
    }
}

class SinglePublicMethodClass
{
    public function run(): void
    {
    }
}

class SinglePublicMethodWithPrivateHelper
{
    public function run(): void
    {
    }

    private function helper(): void
    {
    }
}

class WithPublicProperty
{
    public string $name;

    public static function foo(): void
    {
    }
}

class WithStaticProperty
{
    private static int $count = 0;

    public static function foo(): void
    {
    }
}

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

class WithConstructorArgs
{
    public function __construct(private int $id)
    {
    }

    public function run(): void
    {
    }
}

class WithConstructorNoArgs
{
    public function __construct()
    {
    }

    public function run(): void
    {
    }
}

class PublicStaticOnlyWithPrivateMethod
{
    public static function foo(): void
    {
    }

    private static function helper(): void
    {
    }
}
