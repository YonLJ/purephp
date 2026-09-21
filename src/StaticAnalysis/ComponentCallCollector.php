<?php

declare(strict_types=1);

namespace Pure\StaticAnalysis;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\MagicConst;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects component names declared or used through the `register()` and
 * `component()` helper functions: `kind` is `registered` for a definition
 * and `call` for a reference.
 *
 * A definition is a name literal (`register('X', ...)`) or the call function
 * itself (`register(Icon(...), ...)` — the first-class callable's name is the
 * registered name). A reference is a name literal (`component('X')`), a unit
 * path (skipped), or `component(__FUNCTION__)`, whose name is the enclosing
 * function's name as PHPStan sees it.
 *
 * @api
 * @implements Collector<FuncCall, array{kind: 'registered'|'call', name: string, file: string, line: int}>
 */
final class ComponentCallCollector implements Collector
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope)
    {
        if (!$node->name instanceof Node\Name) {
            return null;
        }

        $called = $scope->resolveName($node->name);
        $first = $node->args[0] ?? null;

        if ($called === 'Pure\Component\register') {
            if (!$first instanceof Arg) {
                return null;
            }

            $value = $first->value;

            if ($value instanceof String_) {
                return ['kind' => 'registered', 'name' => $value->value, 'file' => $scope->getFile(), 'line' => $node->getLine()];
            }

            // register(Icon(...)) — a first-class callable of the call
            // function; its name is what register() reflects at runtime.
            if (
                $value instanceof FuncCall
                && $value->name instanceof Node\Name
                && count($value->args) === 1
                && $value->args[0] instanceof VariadicPlaceholder
            ) {
                return ['kind' => 'registered', 'name' => $scope->resolveName($value->name), 'file' => $scope->getFile(), 'line' => $node->getLine()];
            }

            return null;
        }

        if ($called !== 'Pure\Component\component') {
            return null;
        }

        if (!$first instanceof Arg) {
            return null;
        }

        $value = $first->value;

        // component(__FUNCTION__) inside the call function: the name is the
        // enclosing function's name, known statically.
        if ($value instanceof MagicConst\Function_) {
            $function = $scope->getFunction();

            if ($function === null || str_contains($function->getName(), '{closure}')) {
                return null;
            }

            return ['kind' => 'call', 'name' => $function->getName(), 'file' => $scope->getFile(), 'line' => $node->getLine()];
        }

        if (!$value instanceof String_) {
            return null;
        }

        $name = $value->value;

        // component() also accepts a unit file path; only a plain name is a
        // component reference.
        if (str_contains($name, '/') || str_contains($name, '\\') || str_ends_with($name, '.php')) {
            return null;
        }

        return ['kind' => 'call', 'name' => $name, 'file' => $scope->getFile(), 'line' => $node->getLine()];
    }
}
