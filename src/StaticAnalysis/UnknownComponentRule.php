<?php

declare(strict_types=1);

namespace Pure\StaticAnalysis;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Pure\Core\Suggestion;

/**
 * Reports a `component('X')` / `Registry::component('X')` whose literal name
 * no analysed `register(Icon(...))` or `Registry::register()` declares: the
 * typo that otherwise only explodes at render time.
 *
 * Needs a full-project run (collected data arrives after the last file) and
 * is disabled for single-file runs via isOnlyFilesAnalysis(). In this
 * repository `tests/` is exempt in phpstan.neon: its fixtures register
 * components from heredoc strings the analyser cannot see.
 *
 * @api
 * @implements Rule<CollectedDataNode>
 */
final class UnknownComponentRule implements Rule
{
    public const IDENTIFIER = 'purephp.unknownComponent';

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isOnlyFilesAnalysis()) {
            return [];
        }

        $registered = [];
        $calls = [];

        foreach ([ComponentCallCollector::class, RegistryCallCollector::class] as $collector) {
            foreach ($node->get($collector) as $entries) {
                foreach ($entries as $entry) {
                    if ($entry['kind'] === 'registered') {
                        $registered[$entry['name']] = true;

                        continue;
                    }

                    $calls[] = $entry;
                }
            }
        }

        if ($registered === []) {
            return [];
        }

        $known = array_keys($registered);
        $errors = [];

        foreach ($calls as $call) {
            if (isset($registered[$call['name']])) {
                continue;
            }

            $nearest = Suggestion::nearest($call['name'], $known);
            $hint = $nearest === null ? '' : " (did you mean '{$nearest}'?)";

            $errors[] = RuleErrorBuilder::message("The component '{$call['name']}' is not registered{$hint}.")
                ->identifier(self::IDENTIFIER)
                ->file($call['file'])
                ->line($call['line'])
                ->build();
        }

        return $errors;
    }
}
