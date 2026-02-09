<?php

declare(strict_types = 1);

namespace Coffiso\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Declare_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function pathinfo;
use function preg_match;
use function sprintf;

/**
 * @implements Rule<Node>
 */
final class TopLevelFunctionFileRule implements Rule {

    private int $functionCountByFile = 0;
    private bool $isFunctionFile = false;
    private bool $isClassFile = false;

    public function getNodeType(): string {
        return Node::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array {
        if ($node instanceof Function_) {
            $errors = [];
            // 名前空間がない関数定義は許可しない
            if ($scope->getNamespace() === null) {
                $errors[] = RuleErrorBuilder::message('Functions in a function file must belong to a namespace.')
                    ->identifier('coffiso.topLevelFunctionFile.noNamespace')
                    ->line($node->getLine())
                    ->build();
            }

            $this->functionCountByFile++;
            // 1ファイル1関数のみ許可
            if ($this->functionCountByFile > 1) {
                $errors[] = RuleErrorBuilder::message('Only one function in a function file is allowed.')
                    ->identifier('coffiso.topLevelFunctionFile.multipleFunctions')
                    ->line($node->getLine())
                    ->build();
            }

            $file = $scope->getFile();
            $fileBaseName = pathinfo($file, PATHINFO_FILENAME);
            // 関数ファイルはロワーキャメルケースでなければならない
            if (!$this->isLowerCamelCase($fileBaseName)) {
                $errors[] = RuleErrorBuilder::message('Function file name must be lowerCamelCase.')
                    ->identifier('coffiso.topLevelFunctionFile.forbidFileNaming')
                    ->line(1)
                    ->build();
            }

            $functionName = $node->name->toString();
            // ファイル名と関数名は一致させなければならない (ファイル名ベースで決定)
            if ($functionName !== $fileBaseName && $errors === []) {
                $errors[] = RuleErrorBuilder::message("The function file name and function name must be the same.")
                    ->identifier('coffiso.topLevelFunctionFile.nameMismatch')
                    ->line($node->getLine())
                    ->build();
            }

            if ($errors !== []) {
                return $errors;
            }

            $this->isFunctionFile = true;
        }

        if ($this->isFunctionFile) {
            $nodeDisplayName = match (true) {
                $node instanceof Class_ => "class",
                $node instanceof Enum_ => "enum",
                $node instanceof Interface_ => "interface",
                $node instanceof Trait_ => "trait",
                default => null,
            };

            if ($nodeDisplayName === null) {
                return [];
            }

            return [
                RuleErrorBuilder::message("{$nodeDisplayName} is not allowed in a function file.")
                    ->identifier('coffiso.topLevelFunctionFile.forbidStatement')
                    ->line($node->getLine())
                    ->build()
            ];
        }

        return [];
    }

    private function isLowerCamelCase(string $name): bool {
        return preg_match('/^[a-z][a-zA-Z0-9]*$/', $name) === 1;
    }
}
