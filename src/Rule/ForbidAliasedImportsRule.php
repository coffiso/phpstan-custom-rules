<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_merge;
use function sprintf;

/**
 * @implements Rule<Node>
 */
final readonly class ForbidAliasedImportsRule implements Rule
{
    public function __construct(
        private bool $allowSameNameAlias,
        private bool $reportGroupUse,
    ) {
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
        if ($node instanceof Use_) {
            return $this->processUse($node);
        }

        if ($this->reportGroupUse && $node instanceof GroupUse) {
            return $this->processGroupUse($node);
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processUse(Use_ $node): array
    {
        $errors = [];

        foreach ($node->uses as $useUse) {
            $errors = array_merge($errors, $this->checkAlias($useUse, $useUse->name));
        }

        return $errors;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processGroupUse(GroupUse $node): array
    {
        $errors = [];

        foreach ($node->uses as $useUse) {
            $fullName = new Name($node->prefix->toString() . '\\' . $useUse->name->toString());
            $errors = array_merge($errors, $this->checkAlias($useUse, $fullName));
        }

        return $errors;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function checkAlias(UseUse $useUse, Name $importedName): array
    {
        if ($useUse->alias === null) {
            return [];
        }

        $alias = $useUse->alias->toString();
        $shortName = $importedName->getLast();

        if ($this->allowSameNameAlias && $alias === $shortName) {
            return [];
        }

        $message = sprintf(
            'Alias import "use %s as %s" is prohibited. If the purpose is to avoid name collisions, use partial namespace imports instead.',
            $importedName->toString(),
            $alias,
        );

        return [RuleErrorBuilder::message($message)
            ->identifier('coffiso.forbidAliasedImports')
            ->line($useUse->getLine())
            ->build()];
    }
}
