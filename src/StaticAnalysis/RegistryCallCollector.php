<?php

declare(strict_types=1);

namespace Pure\StaticAnalysis;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects component names declared or used through the static facade:
 * `kind` is `registered` for `Registry::register('X', ...)` and `call` for
 * `Registry::component('X')`.
 *
 * @api
 * @implements Collector<StaticCall, array{kind: 'registered'|'call', name: string, file: string, line: int}>
 */
final class RegistryCallCollector implements Collector
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope)
    {
        if (!$node->class instanceof Node\Name) {
            return null;
        }

        if ($scope->resolveName($node->class) !== 'Pure\Component\Registry') {
            return null;
        }

        $method = $node->name instanceof Node\Identifier ? $node->name->toString() : '';
        $first = $node->args[0] ?? null;

        if ($method === 'register') {
            if (!$first instanceof Arg || !$first->value instanceof String_) {
                return null;
            }

            return ['kind' => 'registered', 'name' => $first->value->value, 'file' => $scope->getFile(), 'line' => $node->getLine()];
        }

        if ($method !== 'component') {
            return null;
        }

        if (!$first instanceof Arg || !$first->value instanceof String_) {
            return null;
        }

        $name = $first->value->value;

        if (str_contains($name, '/') || str_contains($name, '\\') || str_ends_with($name, '.php')) {
            return null;
        }

        return ['kind' => 'call', 'name' => $name, 'file' => $scope->getFile(), 'line' => $node->getLine()];
    }
}
