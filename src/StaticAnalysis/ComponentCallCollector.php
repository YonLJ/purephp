<?php

declare(strict_types=1);

namespace Pure\StaticAnalysis;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects component names declared or used through the `register()` and
 * `component()` helper functions: `kind` is `registered` for a definition
 * and `call` for a `component('...')` reference.
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
            if (!$first instanceof Arg || !$first->value instanceof String_) {
                return null;
            }

            return ['kind' => 'registered', 'name' => $first->value->value, 'file' => $scope->getFile(), 'line' => $node->getLine()];
        }

        if ($called !== 'Pure\Component\component') {
            return null;
        }

        if (!$first instanceof Arg || !$first->value instanceof String_) {
            return null;
        }

        $name = $first->value->value;

        // component() also accepts a unit file path; only a plain name is a
        // component reference.
        if (str_contains($name, '/') || str_contains($name, '\\') || str_ends_with($name, '.php')) {
            return null;
        }

        return ['kind' => 'call', 'name' => $name, 'file' => $scope->getFile(), 'line' => $node->getLine()];
    }
}
