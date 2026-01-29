<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Tests\Rule;

use Coffiso\PHPStan\Rule\ForbidCustomTypesRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<ForbidCustomTypesRule>
 */
final class ForbidCustomTypesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidCustomTypesRule(
            forbiddenTypes: [
                // シンプル形式
                'ForbidCustomTypesRuleTest\ForbiddenClass' => 'This class is forbidden.',
                'ForbidCustomTypesRuleTest\ForbiddenInterface' => 'This interface is forbidden.',
                'ForbidCustomTypesRuleTest\ForbiddenTrait' => 'This trait is forbidden.',
                // 詳細形式: typeHintOnly（型ヒントとしての使用のみ禁止）
                'ForbidCustomTypesRuleTest\TypeHintOnlyForbiddenClass' => [
                    'description' => 'This class is forbidden as type hint only.',
                    'typeHintOnly' => true,
                    'withSubclasses' => false,
                ],
                // 詳細形式: withSubclasses（サブクラスも禁止）
                'ForbidCustomTypesRuleTest\ForbiddenBaseClass' => [
                    'description' => 'This class and its subclasses are forbidden.',
                    'typeHintOnly' => false,
                    'withSubclasses' => true,
                ],
            ],
            reflectionProvider: self::getContainer()->getByType(ReflectionProvider::class),
            fileTypeMapper: self::getContainer()->getByType(FileTypeMapper::class),
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/ForbidCustomTypesRuleTest.php'], [
            // SubclassOfForbiddenBase extends ForbiddenBaseClass (withSubclasses=true)
            [
                'Class ForbidCustomTypesRuleTest\SubclassOfForbiddenBase extends forbidden type ForbidCustomTypesRuleTest\ForbiddenBaseClass. This class and its subclasses are forbidden.',
                31,
            ],
            // プロパティの型チェック
            [
                'Property ForbidCustomTypesRuleTest\TestClass::$forbiddenProperty has forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                42,
            ],
            // PHPDoc @var のチェック
            [
                'PHPDoc @var for property ForbidCustomTypesRuleTest\TestClass::$forbiddenVarProperty contains forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                45,
            ],
            // 引数の型チェック
            [
                'Parameter $forbidden of ForbidCustomTypesRuleTest\TestClass::methodWithForbiddenParam() has forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                49,
            ],
            // 戻り値の型チェック
            [
                'Return type of ForbidCustomTypesRuleTest\TestClass::methodWithForbiddenReturn() has forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                54,
            ],
            // extends のチェック
            [
                'Class ForbidCustomTypesRuleTest\ClassExtendingForbidden extends forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                65,
            ],
            // implements のチェック
            [
                'Class ForbidCustomTypesRuleTest\ClassImplementingForbidden implements forbidden type ForbidCustomTypesRuleTest\ForbiddenInterface. This interface is forbidden.',
                70,
            ],
            // trait use のチェック
            [
                'Class ForbidCustomTypesRuleTest\ClassUsingForbiddenTrait uses forbidden trait ForbidCustomTypesRuleTest\ForbiddenTrait. This trait is forbidden.',
                77,
            ],
            // PHPDoc @param のチェック
            [
                'PHPDoc @param $forbidden of ForbidCustomTypesRuleTest\TestClass2::methodWithForbiddenPhpDocParam() contains forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                87,
            ],
            // PHPDoc @return のチェック
            [
                'PHPDoc @return of ForbidCustomTypesRuleTest\TestClass2::methodWithForbiddenPhpDocReturn() contains forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                95,
            ],
            // Union型での禁止型チェック
            [
                'Parameter $param of ForbidCustomTypesRuleTest\TestClass2::methodWithUnionParam() has forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                104,
            ],
            // Nullable型での禁止型チェック
            [
                'Parameter $param of ForbidCustomTypesRuleTest\TestClass2::methodWithNullableParam() has forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                109,
            ],
            // typeHintOnly: 引数での使用は検出される
            [
                'Parameter $param of ForbidCustomTypesRuleTest\TestClass3::methodWithTypeHintOnlyForbidden() has forbidden type ForbidCustomTypesRuleTest\TypeHintOnlyForbiddenClass. This class is forbidden as type hint only.',
                126,
            ],
            // withSubclasses: サブクラスの使用も検出
            [
                'Parameter $param of ForbidCustomTypesRuleTest\TestClass3::methodWithSubclassOfForbidden() has forbidden type ForbidCustomTypesRuleTest\SubclassOfForbiddenBase. This class and its subclasses are forbidden. (subclass of ForbidCustomTypesRuleTest\ForbiddenBaseClass)',
                131,
            ],
            // extends でサブクラスチェック
            [
                'Class ForbidCustomTypesRuleTest\ClassExtendingForbiddenBase extends forbidden type ForbidCustomTypesRuleTest\ForbiddenBaseClass. This class and its subclasses are forbidden.',
                137,
            ],
            // SubclassOfForbidden extends ForbiddenClass (withSubclasses=false なので本来は検出されないはずだが、extendsそのものはForbiddenClassを直接指定しているので検出される)
            [
                'Class ForbidCustomTypesRuleTest\SubclassOfForbidden extends forbidden type ForbidCustomTypesRuleTest\ForbiddenClass. This class is forbidden.',
                186,
            ],
        ]);
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../rules.neon'];
    }
}
