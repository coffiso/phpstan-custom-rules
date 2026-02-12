<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\ConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NullType;

use function in_array;
use function sprintf;
use function strtolower;

/**
 * Detects redundant comparisons of bool-typed expressions with true/false/null literals.
 *
 * Examples of violations:
 *   $foo === true   (when $foo is bool)
 *   $foo == false   (when $foo is bool)
 *   $foo !== null   (when $foo is bool)
 *   foo() === true  (when foo() returns bool)
 *
 * @implements Rule<BinaryOp>
 */
final readonly class RedundantBoolComparisonRule implements Rule
{
    private const BOOL_LITERALS = ['true', 'false', 'null'];

    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (
            !$node instanceof Identical
            && !$node instanceof Equal
            && !$node instanceof NotIdentical
            && !$node instanceof NotEqual
        ) {
            return [];
        }

        // Determine which side is the literal and which is the expression
        $literalSide = $this->extractLiteral($node->right);

        if ($literalSide !== null) {
            $exprSide = $node->left;
        } else {
            $literalSide = $this->extractLiteral($node->left);

            if ($literalSide === null) {
                return [];
            }

            $exprSide = $node->right;
        }

        // Check if the expression side has a bool type
        $exprType = $scope->getType($exprSide);

        if (!$exprType instanceof BooleanType && !$exprType instanceof ConstantBooleanType) {
            return [];
        }

        $operator = $this->getOperatorString($node);
        $literalName = $literalSide;

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Redundant comparison: bool expression %s %s is always resolvable without explicit comparison. Use the bool expression directly.',
                    $operator,
                    $literalName,
                ),
            )
                ->identifier('coffiso.redundantBoolComparison')
                ->build(),
        ];
    }

    private function extractLiteral(Expr $expr): ?string
    {
        if (!$expr instanceof ConstFetch) {
            return null;
        }

        $name = strtolower($expr->name->toString());

        if (!in_array($name, self::BOOL_LITERALS, true)) {
            return null;
        }

        return $name;
    }

    private function getOperatorString(BinaryOp $node): string
    {
        if ($node instanceof Identical) {
            return '===';
        }

        if ($node instanceof NotIdentical) {
            return '!==';
        }

        if ($node instanceof Equal) {
            return '==';
        }

        return '!=';
    }
}
