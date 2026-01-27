# Coffiso PHPStan Custom Rules

[![CI](https://github.com/coffiso/phpstan-custom-rules/actions/workflows/ci.yml/badge.svg)](https://github.com/coffiso/phpstan-custom-rules/actions/workflows/ci.yml)

PHPStan の型解析機能を活用したカスタムルール集です。

## 要件

- PHP 8.2 以上
- PHPStan 2.0 以上

## インストール

GitHub リポジトリから直接インストールします。プロジェクトの `composer.json` に以下を追加してください：

### 方法1: 最新の main ブランチを使用

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/coffiso/phpstan-custom-rules.git"
        }
    ],
    "require-dev": {
        "coffiso/phpstan-custom-rules": "dev-main"
    }
}
```

その後、以下を実行します：

```bash
composer update
```

### 方法2: リリースバージョンを使用

リポジトリでリリースを公開している場合、バージョンタグで指定できます：

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/coffiso/phpstan-custom-rules.git"
        }
    ],
    "require-dev": {
        "coffiso/phpstan-custom-rules": "^1.0"
    }
}
```

### 方法3: コマンドラインで直接指定

```bash
composer require --dev "coffiso/phpstan-custom-rules:dev-main" --repository-url=https://github.com/coffiso/phpstan-custom-rules.git
```

## 設定

`phpstan.neon` に以下を追加します：

```neon
includes:
    - vendor/coffiso/phpstan-custom-rules/rules.neon
```

## 利用可能なルール

### RequireReadonlyClassRule

`readonly class` として定義できる条件を満たしているクラスに警告を出すルールです。

PHP 8.2 で導入された `readonly class` を積極的に使用することで、イミュータブルなクラス設計を促進します。

#### 検出条件

以下の条件を**すべて**満たすクラスに対して警告を出します：

1. すでに `readonly class` として宣言されていない
2. すべてのインスタンスプロパティが `readonly` 修飾子を持つ
3. `static` プロパティを持たない（`readonly class` では `static` プロパティを持てないため）
4. すべてのプロパティに型宣言がある（`readonly class` では型宣言が必須のため）
5. 親クラスがある場合、親クラスも `readonly` である

#### 設定オプション

```neon
parameters:
    coffisoRules:
        requireReadonlyClass:
            enabled: true                                # ルールの有効/無効
            reportAbstractClasses: true                  # abstractクラスも報告するか
            reportClassesExtendingNonReadonlyParent: false  # 非readonlyな親を持つクラスを報告するか
```

#### 良い例

```php
// OK: すでにreadonly classとして宣言されている
readonly class User
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}

// OK: staticプロパティを持つため、readonly classにできない
class Counter
{
    public readonly string $name;
    public static int $count = 0;
}

// OK: 非readonlyプロパティを持つ
class MutableEntity
{
    public readonly int $id;
    public string $name; // readonlyではない
}
```

#### 悪い例（警告が出る）

```php
// NG: すべてのプロパティがreadonlyなので、readonly classにすべき
class User
{
    public readonly string $name;
    public readonly int $age;
}

// NG: プロモートプロパティがすべてreadonly
class Product
{
    public function __construct(
        public readonly string $name,
        public readonly int $price,
    ) {
    }
}
```

これらは以下のように修正すべきです：

```php
readonly class User
{
    public string $name;
    public int $age;
}

readonly class Product
{
    public function __construct(
        public string $name,
        public int $price,
    ) {
    }
}
```

## 開発

### セットアップ

```bash
git clone https://github.com/coffiso/phpstan-custom-rules.git
cd phpstan-custom-rules
composer install
```

### テスト実行

```bash
# すべてのチェックを実行
composer check

# 個別のチェック
composer check:types   # PHPStan
composer check:tests   # PHPUnit
composer check:cs      # PHP_CodeSniffer
```

### リリース方法

バージョンタグを切ることで、特定のバージョンを参照可能にします：

```bash
# バージョンタグを作成（例：v1.0.0）
git tag v1.0.0
git push origin v1.0.0

# または GitHub UI からリリースを作成
```

ユーザーは以下のように特定のバージョンを指定できます：

```json
{
    "require-dev": {
        "coffiso/phpstan-custom-rules": "v1.0.0"
    }
}
```

### 新しいルールの追加

1. `src/Rule/` に新しいルールクラスを作成
2. `rules.neon` にサービス登録を追加
3. `tests/Rule/` にテストクラスを作成
4. `tests/Rule/data/` にテストデータを作成

#### ルールの基本構造

```php
<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 */
final class YourCustomRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // ルール実装
        return [];
    }
}
```

## ライセンス

MIT License
