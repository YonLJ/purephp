<?php

declare(strict_types=1);

namespace Pure\StaticAnalysis;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use Pure\Component\Component;

/**
 * Collects the component name a `#[Component('X')]` attribute pins on a
 * call function; a nameless `#[Component]` declares no name and is skipped
 * (the name comes from the file's register() call, which
 * ComponentCallCollector sees).
 *
 * @api
 * @implements Collector<Function_, array{kind: 'registered', name: string, file: string, line: int}>
 */
final class ComponentAttributeCollector implements Collector
{
    public function getNodeType(): string
    {
        return Function_::class;
    }

    public function processNode(Node $node, Scope $scope)
    {
        foreach ($node->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if ($scope->resolveName($attribute->name) !== Component::class) {
                    continue;
                }

                $name = null;

                if ($attribute->args !== []) {
                    $first = $attribute->args[0];

                    if ($first->value instanceof Node\Scalar\String_) {
                        $name = $first->value->value;
                    }
                }

                if ($name === null) {
                    return null;
                }

                return ['kind' => 'registered', 'name' => $name, 'file' => $scope->getFile(), 'line' => $node->getLine()];
            }
        }

        return null;
    }
}
