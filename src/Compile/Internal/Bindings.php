<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Pure\Component\Binds;
use ReflectionFunction;

/**
 * Static reads of the PHP in a unit file: the variables a function body uses,
 * and the binding keys a prepare() hook returns or declares.
 *
 * `pure check` uses them for the unused-parameter warning and to compare the
 * bindings of a prepare() hook against the slots its template reads. The scans
 * are deliberately conservative: what they cannot read is reported as unknown
 * instead of guessed.
 *
 * @internal
 */
final class Bindings
{
    /**
     * The variables a function body reads, or null when its source cannot be
     * read. Used to tell a parameter the body never touches from one it binds.
     *
     * @param ReflectionFunction $function The function to read.
     * @return array<string, true>|null The variable names, keyed as a set.
     */
    public static function variables(ReflectionFunction $function): ?array
    {
        $tokens = self::tokens($function);

        if ($tokens === null) {
            return null;
        }

        $bodyStart = 0;

        foreach ($tokens as $index => $token) {
            if ($token === '{') {
                $bodyStart = $index;

                break;
            }
        }

        $variables = [];
        $count = count($tokens);

        for ($index = $bodyStart + 1; $index < $count; $index++) {
            $token = $tokens[$index];

            if (is_array($token) && $token[0] === T_VARIABLE) {
                $variables[substr($token[1], 1)] = true;
            }
        }

        return $variables;
    }

    /**
     * The keys a `#[Binds]` attribute declares on a function that returns
     * bindings, for when its array literal cannot be read.
     *
     * @param ReflectionFunction $function The prepare() hook.
     * @return array<string, true>|null The declared keys, or null without the attribute.
     */
    public static function declaredKeys(ReflectionFunction $function): ?array
    {
        foreach ($function->getAttributes(Binds::class) as $attribute) {
            $keys = [];

            foreach ($attribute->newInstance()->keys as $key) {
                $keys[$key] = true;
            }

            return $keys;
        }

        return null;
    }

    /**
     * The string keys of a function whose only return is an array literal: the
     * bindings of a prepare() hook, or null when the keys are branch-dependent
     * or computed.
     *
     * @param ReflectionFunction $function The prepare() hook.
     * @return array<string, true>|null
     */
    public static function literalKeys(ReflectionFunction $function): ?array
    {
        $tokens = self::tokens($function);

        if ($tokens === null) {
            return null;
        }

        $count = count($tokens);
        $return = null;

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];

            if (!is_array($token) || $token[0] !== T_RETURN) {
                continue;
            }

            if ($return !== null) {
                return null; // several returns: the key set is branch-dependent
            }

            $return = $index;
        }

        if ($return === null) {
            return null;
        }

        $open = self::significant($tokens, $return, 1);

        if ($open !== '[') {
            return null;
        }

        $openIndex = $return + 1;

        while ($openIndex < $count && $tokens[$openIndex] !== '[') {
            $openIndex++;
        }

        $keys = [];
        $depth = 0;
        $expectKey = true;

        for ($index = $openIndex + 1; $index < $count; $index++) {
            $token = $tokens[$index];

            if (
                $token === '[' || $token === '(' || $token === '{'
                || is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)
            ) {
                $depth++;

                continue;
            }

            if ($token === ']' && $depth === 0) {
                return $keys;
            }

            if ($token === ']' || $token === ')' || $token === '}') {
                // An interpolation or heredoc body can close more than it
                // opens: never let the depth go negative.
                $depth = max(0, $depth - 1);

                continue;
            }

            if ($depth > 0 || is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($token === ',') {
                $expectKey = true;

                continue;
            }

            if (!$expectKey) {
                continue;
            }

            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                if (!self::isDoubleArrow(self::significant($tokens, $index, 1))) {
                    return null; // a positional value: the literal is a list
                }

                $keys[self::unquote($token[1])] = true;
                $expectKey = false;

                continue;
            }

            if (self::isDoubleArrow($token)) {
                continue;
            }

            return null; // numeric keys, variables, spreads: unknown
        }

        return null;
    }

    /**
     * @param ReflectionFunction $function The function to read.
     * @return list<array{0: int, 1: string, 2: int}|string>|null The function's tokens, or null.
     */
    private static function tokens(ReflectionFunction $function): ?array
    {
        $file = $function->getFileName();
        $start = $function->getStartLine();
        $end = $function->getEndLine();

        if ($file === false || !is_file($file) || $start < 1 || $end < $start) {
            return null;
        }

        $lines = file($file);

        if ($lines === false) {
            return null;
        }

        /** @var list<array{0: int, 1: string, 2: int}|string> $tokens */
        $tokens = token_get_all('<?php ' . implode('', array_slice($lines, $start - 1, $end - $start + 1)));

        return $tokens;
    }

    /**
     * @param array{0: int, 1: string, 2: int}|string|null $token
     */
    private static function isDoubleArrow(array|string|null $token): bool
    {
        return $token === '=>' || (is_array($token) && $token[0] === T_DOUBLE_ARROW);
    }

    private static function unquote(string $literal): string
    {
        $body = substr($literal, 1, -1);

        if ($literal[0] === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $body);
        }

        return stripcslashes($body);
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return array{0: int, 1: string, 2: int}|string|null
     */
    private static function significant(array $tokens, int $index, int $direction): array|string|null
    {
        $cursor = $index + $direction;

        while (isset($tokens[$cursor])) {
            $token = $tokens[$cursor];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $cursor += $direction;

                continue;
            }

            return $token;
        }

        return null;
    }
}
