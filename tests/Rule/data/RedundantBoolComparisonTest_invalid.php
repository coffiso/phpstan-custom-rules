<?php

declare(strict_types=1);

namespace RedundantBoolComparisonTest;

// --- NG cases: redundant comparisons with bool expressions ---

/** @var bool $boolVar */
$boolVar = true;

// Identical (===) comparisons with true/false/null
if ($boolVar === true) {} // line 13: NG
if ($boolVar === false) {} // line 14: NG
if ($boolVar === null) {} // line 15: NG

// Equal (==) comparisons with true/false/null
if ($boolVar == true) {} // line 18: NG
if ($boolVar == false) {} // line 19: NG
if ($boolVar == null) {} // line 20: NG

// Not-identical (!==) comparisons with true/false/null
if ($boolVar !== true) {} // line 23: NG
if ($boolVar !== false) {} // line 24: NG
if ($boolVar !== null) {} // line 25: NG

// Not-equal (!=) comparisons with true/false/null
if ($boolVar != true) {} // line 28: NG
if ($boolVar != false) {} // line 29: NG
if ($boolVar != null) {} // line 30: NG

// Reversed order (literal on left side)
if (true === $boolVar) {} // line 33: NG
if (false === $boolVar) {} // line 34: NG
if (null == $boolVar) {} // line 35: NG

// Expression returning bool (built-in functions)
if (isset($_GET['bar']) === true) {} // line 38: NG
if (isset($_GET['bar']) == false) {} // line 39: NG
if (isset($_GET['bar']) !== true) {} // line 40: NG
if (is_string('hello') != false) {} // line 41: NG
