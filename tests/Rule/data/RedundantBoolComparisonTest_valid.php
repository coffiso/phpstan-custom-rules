<?php

declare(strict_types=1);

namespace RedundantBoolComparisonTest;

// --- OK cases: these should NOT be reported ---

/** @var bool $boolVar */
$boolVar = true;

// Direct use of bool (no comparison)
if ($boolVar) {} // OK
if (!$boolVar) {} // OK

// Function returning bool used directly
function isReady(): bool
{
    return true;
}

if (isReady()) {} // OK
if (!isReady()) {} // OK

// Non-bool types compared with true/false/null (should not trigger)

/** @var int $intVar */
$intVar = 1;
if ($intVar === true) {} // OK - not a bool type
if ($intVar == false) {} // OK - not a bool type

/** @var string $stringVar */
$stringVar = 'hello';
if ($stringVar === true) {} // OK - not a bool type
if ($stringVar == null) {} // OK - not a bool type

/** @var string|null $nullableString */
$nullableString = null;
if ($nullableString === null) {} // OK - not a bool type
if ($nullableString !== null) {} // OK - not a bool type

// Non-bool comparisons (no literals)

/** @var int $a */
$a = 1;
/** @var int $b */
$b = 2;
if ($a === $b) {} // OK - no bool literal
if ($a == $b) {} // OK - no bool literal

// Mixed type (not pure bool)

/** @var bool|string $mixed */
$mixed = true;
if ($mixed === true) {} // OK - not purely bool type
