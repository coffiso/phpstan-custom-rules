<?php

declare(strict_types=1);

namespace ForbidAliasedImportsRuleTest\Vendor;

class Bar
{
}

class Baz
{
}

class Qux
{
}

class Corge
{
}

namespace ForbidAliasedImportsRuleTest;

use ForbidAliasedImportsRuleTest\Vendor\Bar as Baz; // error
use ForbidAliasedImportsRuleTest\Vendor\Qux; // ok
use ForbidAliasedImportsRuleTest\Vendor\Corge as Corge; // error
use ForbidAliasedImportsRuleTest\Vendor\Baz as BazAlias; // error
use ForbidAliasedImportsRuleTest\Vendor\{Bar as BarAlias, Qux, Corge as Corge}; // errors

class Demo
{
}
