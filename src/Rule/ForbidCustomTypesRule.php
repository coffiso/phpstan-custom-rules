<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;

use function array_key_exists;
use function array_merge;
use function explode;
use function is_string;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * PHPStanのカスタムルール：指定された型の使用を検出して警告する
 *
 * 検出対象：
 * - 引数での型の使用（ネイティブ型ヒント）
 * - 戻り値での型の使用（ネイティブ型ヒント）
 * - 継承(extends)での型の使用
 * - 実装(implements)での型の使用
 * - トレイト(use)での型の使用
 * - PHPDocコメント内での型の使用（@var, @param, @return）
 * - プロパティでの型の使用（ネイティブ型ヒント）
 *
 * 設定オプション（型毎に指定可能）：
 * - typeHintOnly: 型ヒントとしての使用のみを禁止（extends/implements/useは許可）
 * - withSubclasses: 指定した型を継承したサブクラスも禁止対象に含める
 *
 * @implements Rule<Node>
 */
final readonly class ForbidCustomTypesRule implements Rule
{
    /**
     * 禁止する型の設定
     * クラス名 => [description, typeHintOnly, withSubclasses]
     *
     * @var array<string, array{description: string, typeHintOnly: bool, withSubclasses: bool}>
     */
    private array $forbiddenTypes;

    /**
     * @param array<mixed> $forbiddenTypes 禁止する型のリスト
     *   形式: ['ClassName' => 'description'] または ['ClassName' => ['description' => '...', 'typeHintOnly' => true, 'withSubclasses' => true]]
     */
    public function __construct(
        array $forbiddenTypes,
        private ReflectionProvider $reflectionProvider,
        private FileTypeMapper $fileTypeMapper,
    ) {
        $this->forbiddenTypes = $this->normalizeForbiddenTypes($forbiddenTypes);
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->forbiddenTypes === []) {
            return [];
        }

        $errors = [];

        // InClassNode: クラス定義（extends, implements, use, プロパティ）をチェック
        // @phpstan-ignore phpstanApi.instanceofAssumption
        if ($node instanceof InClassNode) {
            $errors = array_merge($errors, $this->processClassNode($node, $scope));
        }

        // FunctionLike: 関数/メソッド定義（引数、戻り値、PHPDoc @param/@return）をチェック
        if ($node instanceof Node\Stmt\Function_) {
            // トップレベル関数: Node\Stmt\Function_ を直接処理
            $errors = array_merge($errors, $this->processFunctionLike($node, $scope));
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            // クラスメソッド: Node\Stmt\ClassMethod を直接処理
            $errors = array_merge($errors, $this->processFunctionLike($node, $scope));
        } elseif ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
            // クロージャとアロー関数
            $errors = array_merge($errors, $this->processFunctionLike($node, $scope));
        }

        // ローカル変数の @var PHPDoc をチェック（Expression文）
        if ($node instanceof Node\Stmt\Expression) {
            $errors = array_merge($errors, $this->processExpressionPhpDoc($node, $scope));
        }

        return $errors;
    }

    /**
     * クラスノードを処理（extends, implements, use, プロパティ）
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processClassNode(InClassNode $node, Scope $scope): array
    {
        $errors = [];
        $classReflection = $node->getClassReflection();
        $classNode = $node->getOriginalNode();

        if (!$classNode instanceof Class_) {
            return [];
        }

        // extends のチェック
        if ($classNode->extends !== null) {
            $extendsName = $classNode->extends->toString();
            $matchResult = $this->matchesForbiddenType($extendsName, false);
            if ($matchResult !== null) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Class %s extends forbidden type %s. %s',
                    $classReflection->getName(),
                    $extendsName,
                    $matchResult['description'],
                ))
                    ->identifier('coffiso.forbiddenExtends')
                    ->line($classNode->extends->getLine())
                    ->build();
            }
        }

        // implements のチェック
        foreach ($classNode->implements as $implement) {
            $implementName = $implement->toString();
            $matchResult = $this->matchesForbiddenType($implementName, false);
            if ($matchResult !== null) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Class %s implements forbidden type %s. %s',
                    $classReflection->getName(),
                    $implementName,
                    $matchResult['description'],
                ))
                    ->identifier('coffiso.forbiddenImplements')
                    ->line($implement->getLine())
                    ->build();
            }
        }

        // trait use のチェック
        foreach ($classNode->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $traitName = $trait->toString();
                    $matchResult = $this->matchesForbiddenType($traitName, false);
                    if ($matchResult !== null) {
                        $errors[] = RuleErrorBuilder::message(sprintf(
                            'Class %s uses forbidden trait %s. %s',
                            $classReflection->getName(),
                            $traitName,
                            $matchResult['description'],
                        ))
                            ->identifier('coffiso.forbiddenTrait')
                            ->line($trait->getLine())
                            ->build();
                    }
                }
            }
        }

        // プロパティのチェック（ネイティブ型ヒント）
        $errors = array_merge($errors, $this->processClassProperties($classNode, $classReflection, $scope));

        // PHPDocの@varチェック（クラスレベルプロパティ）
        $errors = array_merge($errors, $this->processPropertyPhpDocs($classNode, $classReflection, $scope));

        return $errors;
    }

    /**
     * クラスのプロパティをチェック（ネイティブ型ヒント）
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processClassProperties(Class_ $classNode, ClassReflection $classReflection, Scope $scope): array
    {
        $errors = [];

        foreach ($classNode->stmts as $stmt) {
            if (!$stmt instanceof Node\Stmt\Property) {
                continue;
            }

            if ($stmt->type === null) {
                continue;
            }

            $typeNames = $this->extractTypeNames($stmt->type);
            foreach ($typeNames as $typeName) {
                $matchResult = $this->matchesForbiddenType($typeName, true);
                if ($matchResult !== null) {
                    $propertyNames = [];
                    foreach ($stmt->props as $prop) {
                        $propertyNames[] = '$' . $prop->name->toString();
                    }
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Property %s::%s has forbidden type %s. %s',
                        $classReflection->getName(),
                        implode(', ', $propertyNames),
                        $typeName,
                        $matchResult['description'],
                    ))
                        ->identifier('coffiso.forbiddenPropertyType')
                        ->line($stmt->type->getLine())
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * プロパティのPHPDocをチェック
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processPropertyPhpDocs(Class_ $classNode, ClassReflection $classReflection, Scope $scope): array
    {
        $errors = [];

        foreach ($classNode->stmts as $stmt) {
            if (!$stmt instanceof Node\Stmt\Property) {
                continue;
            }

            $docComment = $stmt->getDocComment();
            if ($docComment === null) {
                continue;
            }

            $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                $scope->getFile(),
                $classReflection->getName(),
                null,
                null,
                $docComment->getText(),
            );

            $varTags = $resolvedPhpDoc->getVarTags();
            foreach ($varTags as $varTag) {
                $type = $varTag->getType();
                $typeNames = $this->extractTypeNamesFromType($type);

                foreach ($typeNames as $typeName) {
                    $matchResult = $this->matchesForbiddenType($typeName, true);
                    if ($matchResult !== null) {
                        $propertyNames = [];
                        foreach ($stmt->props as $prop) {
                            $propertyNames[] = '$' . $prop->name->toString();
                        }
                        // @var タグの行番号を探索
                        $docStartLine = $docComment->getStartLine();
                        $docText = $docComment->getText();
                        $docLines = explode("\n", $docText);
                        $varLine = $this->findPhpDocTagLine($docLines, '@var', null, $docStartLine);
                        
                        $errors[] = RuleErrorBuilder::message(sprintf(
                            'PHPDoc @var for property %s::%s contains forbidden type %s. %s',
                            $classReflection->getName(),
                            implode(', ', $propertyNames),
                            $typeName,
                            $matchResult['description'],
                        ))
                            ->identifier('coffiso.forbiddenPhpDocVarType')
                            ->line($varLine)
                            ->build();
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * 関数/メソッドを処理（引数、戻り値、PHPDoc）
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processFunctionLike(Node\FunctionLike $node, Scope $scope): array
    {
        $errors = [];

        $functionName = $this->getFunctionName($node, $scope);

        // 引数の型チェック（ネイティブ型ヒント）
        foreach ($node->getParams() as $param) {
            if ($param->type === null) {
                continue;
            }

            $typeNames = $this->extractTypeNames($param->type);
            foreach ($typeNames as $typeName) {
                $matchResult = $this->matchesForbiddenType($typeName, true);
                if ($matchResult !== null) {
                    $paramName = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
                        ? '$' . $param->var->name
                        : '(unknown)';
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Parameter %s of %s has forbidden type %s. %s',
                        $paramName,
                        $functionName,
                        $typeName,
                        $matchResult['description'],
                    ))
                        ->identifier('coffiso.forbiddenParamType')
                        ->line($param->type->getLine())
                        ->build();
                }
            }
        }

        // 戻り値の型チェック（ネイティブ型ヒント）
        $returnType = $node->getReturnType();
        if ($returnType !== null) {
            $typeNames = $this->extractTypeNames($returnType);
            foreach ($typeNames as $typeName) {
                $matchResult = $this->matchesForbiddenType($typeName, true);
                if ($matchResult !== null) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Return type of %s has forbidden type %s. %s',
                        $functionName,
                        $typeName,
                        $matchResult['description'],
                    ))
                        ->identifier('coffiso.forbiddenReturnType')
                        ->line($returnType->getLine())
                        ->build();
                }
            }
        }

        // PHPDocのチェック（@param, @return）
        $errors = array_merge($errors, $this->processFunctionPhpDoc($node, $scope, $functionName));

        return $errors;
    }

    /**
     * 関数/メソッドのPHPDocをチェック
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processFunctionPhpDoc(Node\FunctionLike $node, Scope $scope, string $functionName): array
    {
        $errors = [];
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return [];
        }

        $className = $scope->getClassReflection()?->getName();
        $traitName = $scope->getTraitReflection()?->getName();

        // トップレベル関数の場合、ノードから関数名を取得
        $funcName = null;
        if ($node instanceof Node\Stmt\Function_) {
            $funcName = $node->name->toString();
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $funcName = $node->name->toString();
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $className,
            $traitName,
            $funcName,
            $docComment->getText(),
        );

        // PHPDocテキストの各行を解析して、タグの行番号を取得するための基準行を計算
        $docStartLine = $docComment->getStartLine();
        $docText = $docComment->getText();
        $docLines = explode("\n", $docText);

        // @param のチェック
        $paramTags = $resolvedPhpDoc->getParamTags();
        foreach ($paramTags as $paramName => $paramTag) {
            $type = $paramTag->getType();
            $typeNames = $this->extractTypeNamesFromType($type);

            foreach ($typeNames as $typeName) {
                $matchResult = $this->matchesForbiddenType($typeName, true);
                if ($matchResult !== null) {
                    // @param タグの行番号を探索
                    $paramLine = $this->findPhpDocTagLine($docLines, '@param', $paramName, $docStartLine);
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'PHPDoc @param $%s of %s contains forbidden type %s. %s',
                        $paramName,
                        $functionName,
                        $typeName,
                        $matchResult['description'],
                    ))
                        ->identifier('coffiso.forbiddenPhpDocParamType')
                        ->line($paramLine)
                        ->build();
                }
            }
        }

        // @return のチェック
        $returnTag = $resolvedPhpDoc->getReturnTag();
        if ($returnTag !== null) {
            $type = $returnTag->getType();
            $typeNames = $this->extractTypeNamesFromType($type);

            foreach ($typeNames as $typeName) {
                $matchResult = $this->matchesForbiddenType($typeName, true);
                if ($matchResult !== null) {
                    // @return タグの行番号を探索
                    $returnLine = $this->findPhpDocTagLine($docLines, '@return', null, $docStartLine);
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'PHPDoc @return of %s contains forbidden type %s. %s',
                        $functionName,
                        $typeName,
                        $matchResult['description'],
                    ))
                        ->identifier('coffiso.forbiddenPhpDocReturnType')
                        ->line($returnLine)
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * Expression文のPHPDocをチェック（ローカル変数の @var）
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processExpressionPhpDoc(Node\Stmt\Expression $node, Scope $scope): array
    {
        $errors = [];
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return [];
        }

        $className = $scope->getClassReflection()?->getName();
        $traitName = $scope->getTraitReflection()?->getName();
        $funcName = $scope->getFunctionName();

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $className,
            $traitName,
            $funcName,
            $docComment->getText(),
        );

        $varTags = $resolvedPhpDoc->getVarTags();
        foreach ($varTags as $varName => $varTag) {
            $type = $varTag->getType();
            $typeNames = $this->extractTypeNamesFromType($type);

            foreach ($typeNames as $typeName) {
                $matchResult = $this->matchesForbiddenType($typeName, true);
                if ($matchResult !== null) {
                    $varDisplayName = is_string($varName) && $varName !== '' ? '$' . $varName : '';
                    // @var タグの行番号を探索
                    $docStartLine = $docComment->getStartLine();
                    $docText = $docComment->getText();
                    $docLines = explode("\n", $docText);
                    $varLine = $this->findPhpDocTagLine($docLines, '@var', null, $docStartLine);
                    
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'PHPDoc @var%s contains forbidden type %s. %s',
                        $varDisplayName !== '' ? ' ' . $varDisplayName : '',
                        $typeName,
                        $matchResult['description'],
                    ))
                        ->identifier('coffiso.forbiddenPhpDocLocalVarType')
                        ->line($varLine)
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * 関数名を取得
     */
    private function getFunctionName(Node\FunctionLike $node, Scope $scope): string
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $className = $scope->getClassReflection()?->getName() ?? '(unknown)';
            return $className . '::' . $node->name->toString() . '()';
        }

        if ($node instanceof Node\Stmt\Function_) {
            return $node->name->toString() . '()';
        }

        if ($node instanceof Node\Expr\Closure) {
            return 'Closure';
        }

        if ($node instanceof Node\Expr\ArrowFunction) {
            return 'Arrow function';
        }

        return '(unknown function)';
    }

    /**
     * ノードから型名を抽出
     *
     * @return list<string>
     */
    private function extractTypeNames(Node $typeNode): array
    {
        $names = [];

        if ($typeNode instanceof Name) {
            $names[] = $typeNode->toString();
        } elseif ($typeNode instanceof Node\Identifier) {
            // string, int などの組み込み型は無視
            $builtinTypes = ['string', 'int', 'float', 'bool', 'array', 'object', 'callable', 'iterable', 'mixed', 'void', 'null', 'false', 'true', 'never', 'self', 'parent', 'static'];
            $typeName = $typeNode->toString();
            if (!in_array($typeName, $builtinTypes, true)) {
                $names[] = $typeName;
            }
        } elseif ($typeNode instanceof Node\UnionType) {
            foreach ($typeNode->types as $type) {
                $names = array_merge($names, $this->extractTypeNames($type));
            }
        } elseif ($typeNode instanceof Node\IntersectionType) {
            foreach ($typeNode->types as $type) {
                $names = array_merge($names, $this->extractTypeNames($type));
            }
        } elseif ($typeNode instanceof Node\NullableType) {
            $names = array_merge($names, $this->extractTypeNames($typeNode->type));
        }

        return $names;
    }

    /**
     * PHPStan Typeオブジェクトから型名を抽出
     *
     * @return list<string>
     */
    private function extractTypeNamesFromType(Type $type): array
    {
        // オブジェクト型のクラス名を取得（Union/Intersection型も自動的に処理される）
        $classNames = $type->getObjectTypeOrClassStringObjectType()->getObjectClassNames();

        return array_values(array_unique($classNames));
    }

    /**
     * 禁止された型にマッチするかチェック
     *
     * @param string $typeName チェックする型名
     * @param bool $isTypeHintContext 型ヒントとしての使用かどうか
     * @return array{description: string, typeHintOnly: bool, withSubclasses: bool}|null マッチした場合は設定を返す
     */
    private function matchesForbiddenType(string $typeName, bool $isTypeHintContext): ?array
    {
        // 完全一致チェック
        if (array_key_exists($typeName, $this->forbiddenTypes)) {
            $config = $this->forbiddenTypes[$typeName];

            // typeHintOnlyが有効で、型ヒントコンテキストでない場合はスキップ
            if ($config['typeHintOnly'] && !$isTypeHintContext) {
                return null;
            }

            return $config;
        }

        // サブクラスチェック
        if ($this->reflectionProvider->hasClass($typeName)) {
            $classReflection = $this->reflectionProvider->getClass($typeName);

            foreach ($this->forbiddenTypes as $forbiddenTypeName => $config) {
                // サブクラス判定が無効の場合はスキップ
                if (!$config['withSubclasses']) {
                    continue;
                }

                // typeHintOnlyが有効で、型ヒントコンテキストでない場合はスキップ
                if ($config['typeHintOnly'] && !$isTypeHintContext) {
                    continue;
                }

                // 禁止型が存在するかチェック
                if (!$this->reflectionProvider->hasClass($forbiddenTypeName)) {
                    continue;
                }

                $forbiddenClassReflection = $this->reflectionProvider->getClass($forbiddenTypeName);

                // サブクラス判定（自身を除く）
                if ($typeName !== $forbiddenTypeName && $classReflection->isSubclassOf($forbiddenClassReflection->getName())) {
                    return [
                        'description' => $config['description'] . sprintf(' (subclass of %s)', $forbiddenTypeName),
                        'typeHintOnly' => $config['typeHintOnly'],
                        'withSubclasses' => $config['withSubclasses'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * PHPDocテキスト内のタグの行番号を探索
     *
     * @param list<string> $docLines PHPDocテキストを行単位で分割したもの
     * @param string $tagName 探索するタグ名（@param, @return など）
     * @param string|null $paramName パラメータ名（@param の場合のみ指定）
     * @param int $docStartLine PHPDocコメントの開始行番号
     * @return int タグの行番号
     */
    private function findPhpDocTagLine(array $docLines, string $tagName, ?string $paramName, int $docStartLine): int
    {
        $tagPattern = $tagName === '@param' && $paramName !== null
            ? sprintf('/\s*\*\s*@param\s+.*\$%s\b/', preg_quote($paramName, '/'))
            : sprintf('/\s*\*\s*%s\b/', preg_quote($tagName, '/'));

        foreach ($docLines as $lineIndex => $line) {
            if (preg_match($tagPattern, $line)) {
                return $docStartLine + $lineIndex;
            }
        }

        // タグが見つからない場合はPHPDocの開始行を返す
        return $docStartLine;
    }

    /**
     * 入力された禁止型リストを正規化
     *
     * @param array<mixed> $forbiddenTypes
     * @return array<string, array{description: string, typeHintOnly: bool, withSubclasses: bool}>
     */
    private function normalizeForbiddenTypes(array $forbiddenTypes): array
    {
        $normalized = [];

        foreach ($forbiddenTypes as $key => $value) {
            if (is_string($key) && is_string($value)) {
                // シンプル形式: 'ClassName' => 'description'
                $normalized[$key] = [
                    'description' => $value,
                    'typeHintOnly' => false,
                    'withSubclasses' => false,
                ];
            } elseif (is_string($key) && is_array($value)) {
                // 詳細形式: 'ClassName' => ['description' => '...', 'typeHintOnly' => true, 'withSubclasses' => true]
                $description = $value['description'] ?? '';
                if (!is_string($description)) {
                    throw new LogicException(sprintf(
                        'Invalid configuration for forbidden type "%s": description must be a string',
                        $key,
                    ));
                }

                $typeHintOnly = $value['typeHintOnly'] ?? false;
                if (!is_bool($typeHintOnly)) {
                    throw new LogicException(sprintf(
                        'Invalid configuration for forbidden type "%s": typeHintOnly must be a boolean',
                        $key,
                    ));
                }

                $withSubclasses = $value['withSubclasses'] ?? false;
                if (!is_bool($withSubclasses)) {
                    throw new LogicException(sprintf(
                        'Invalid configuration for forbidden type "%s": withSubclasses must be a boolean',
                        $key,
                    ));
                }

                $normalized[$key] = [
                    'description' => $description,
                    'typeHintOnly' => $typeHintOnly,
                    'withSubclasses' => $withSubclasses,
                ];
            } else {
                throw new LogicException(sprintf(
                    'Invalid configuration for forbidden type: key must be string, value must be string or array. Got key type: %s, value type: %s',
                    gettype($key),
                    gettype($value),
                ));
            }
        }

        return $normalized;
    }
}
