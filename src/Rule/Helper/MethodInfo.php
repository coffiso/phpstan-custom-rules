<?php

declare(strict_types=1);

namespace Coffiso\PHPStan\Rule\Helper;

/**
 * クラスのメソッド構成情報を保持する値オブジェクト。
 */
final readonly class MethodInfo
{
    public function __construct(
        public int $publicMethodCount,
        public string $singlePublicMethodName,
        public bool $hasAnyMethod,
        public bool $allMethodsPublicStatic,
    ) {
    }
}
