<?php

declare(strict_types=1);

namespace RedundantBoolComparisonTest;

// --- NG cases: redundant comparisons with bool expressions ---

/** @var bool $boolVar */
$boolVar = true;

// Identical (===) comparisons with true/false
if ($boolVar === true) {} // line 13: NG
if ($boolVar === false) {} // line 14: NG

// Equal (==) comparisons with true/false
if ($boolVar == true) {} // line 17: NG
if ($boolVar == false) {} // line 18: NG

// Not-identical (!==) comparisons with true/false
if ($boolVar !== true) {} // line 21: NG
if ($boolVar !== false) {} // line 22: NG

// Not-equal (!=) comparisons with true/false
if ($boolVar != true) {} // line 25: NG
if ($boolVar != false) {} // line 26: NG

// Reversed order (literal on left side)
if (true === $boolVar) {} // line 29: NG
if (false === $boolVar) {} // line 30: NG

// Expression returning bool (built-in functions)
if (isset($_GET['bar']) === true) {} // line 38: NG
if (isset($_GET['bar']) == false) {} // line 39: NG
if (isset($_GET['bar']) !== true) {} // line 40: NG
if (is_string('hello') != false) {} // line 41: NG
