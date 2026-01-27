<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule;

/**
 * プロパティチェック結果を保持するDTO
 */
final readonly class PropertyCheckResult
{
    public function __construct(
        public bool $canBeReadonly,
        public bool $hasStaticProperty,
        public bool $hasUntypedProperty,
        public bool $hasNonReadonlyProperty,
        public int $propertyCount,
        public int $readonlyPropertyCount,
    ) {
    }
}
