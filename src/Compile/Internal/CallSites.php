<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

/**
 * Static view of the fluent component calls in a file, for `pure check`.
 *
 * A site is a plain function call to a registered component name followed by
 * the `->prop(...)` chain, e.g. `Card($children)->type('Free')->text('Sign
 * up')`. The scan is deliberately conservative: a spread argument marks the
 * site dynamic (its props are not compared), and only names the checker
 * already resolved are considered.
 *
 * @internal
 */
final class CallSites
{
    private function __construct()
    {
    }

    /**
     * @param string $file The file to scan.
     * @param list<string> $names The registered component names.
     * @return list<array{name: string, props: array<string, true>, dynamic: bool}>
     */
    public static function of(string $file, array $names): array
    {
        if ($names === [] || !is_file($file)) {
            return [];
        }

        $source = file_get_contents($file);

        if ($source === false) {
            return [];
        }

        /** @var list<array{0: int, 1: string, 2: int}|string> $tokens */
        $tokens = token_get_all('<?php ' . $source);
        $known = array_flip($names);
        $count = count($tokens);
        $sites = [];

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];

            if (!is_array($token) || $token[0] !== T_STRING || !isset($known[$token[1]])) {
                continue;
            }

            $previous = self::significant($tokens, $index, -1);

            if (
                is_array($previous)
                && in_array($previous[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_FUNCTION, T_ATTRIBUTE], true)
            ) {
                continue;
            }

            if (self::significant($tokens, $index, 1) !== '(') {
                continue;
            }

            $end = self::closing($tokens, $index);

            if ($end === null) {
                continue;
            }

            [$props, $dynamic] = self::chain($tokens, $end);

            $sites[] = ['name' => $token[1], 'props' => $props, 'dynamic' => $dynamic];
        }

        return $sites;
    }

    /**
     * The `->prop(...)` chain after a call, plus whether it is dynamic.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return array{0: array<string, true>, 1: bool}
     */
    private static function chain(array $tokens, int $end): array
    {
        $props = [];
        $dynamic = false;
        $cursor = $end;

        while (true) {
            $operatorIndex = self::next($tokens, $cursor);
            $operator = $tokens[$operatorIndex] ?? null;

            if (!is_array($operator) || $operator[0] !== T_OBJECT_OPERATOR) {
                break;
            }

            $nameIndex = self::next($tokens, $operatorIndex);
            $name = $tokens[$nameIndex] ?? null;

            if (!is_array($name) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name[1]) !== 1) {
                break;
            }

            $openIndex = self::next($tokens, $nameIndex);

            if (!isset($tokens[$openIndex]) || $tokens[$openIndex] !== '(') {
                break;
            }

            $close = self::closing($tokens, $openIndex);

            if ($close === null) {
                break;
            }

            if (self::hasSpread($tokens, $openIndex, $close)) {
                $dynamic = true;
            } else {
                $props[$name[1]] = true;
            }

            $cursor = $close;
        }

        return [$props, $dynamic];
    }

    /**
     * The index of the token that closes the parentheses opened at $index.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function closing(array $tokens, int $index): ?int
    {
        $count = count($tokens);
        $depth = 0;

        for ($cursor = $index; $cursor < $count; $cursor++) {
            $token = $tokens[$cursor];

            if ($token === '(' || $token === '[' || $token === '{') {
                $depth++;
            } elseif ($token === ')' || $token === ']' || $token === '}') {
                $depth--;

                if ($depth === 0) {
                    return $cursor;
                }
            }
        }

        return null;
    }

    /**
     * Whether an argument of the call is unpacked (`->prop(...$values)`), which
     * makes the prop set unknowable.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function hasSpread(array $tokens, int $open, int $close): bool
    {
        $depth = 1;

        for ($cursor = $open + 1; $cursor < $close; $cursor++) {
            $token = $tokens[$cursor];

            if ($token === '(' || $token === '[' || $token === '{') {
                $depth++;
            } elseif ($token === ')' || $token === ']' || $token === '}') {
                $depth = max(1, $depth - 1);
            } elseif ($depth === 1 && is_array($token) && $token[0] === T_ELLIPSIS) {
                return true;
            }
        }

        return false;
    }

    /**
     * The index of the next significant token, or -1 at the end.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function next(array $tokens, int $index): int
    {
        $cursor = $index + 1;
        $count = count($tokens);

        while ($cursor < $count) {
            $token = $tokens[$cursor];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $cursor++;

                continue;
            }

            return $cursor;
        }

        return -1;
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
