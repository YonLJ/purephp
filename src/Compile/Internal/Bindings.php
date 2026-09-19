<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use ReflectionFunction;

/**
 * Static view of the `render()` calls inside a component function.
 *
 * `pure check` uses it to compare the bindings a component function passes
 * against the slots its template reads. The scan is deliberately conservative:
 * it only understands calls whose first argument is a literal string or
 * `__FILE__`, and it reports `dynamic` for an unpacked bindings array, so a
 * function that builds its bindings at runtime is skipped instead of misread.
 *
 * @internal
 */
final class Bindings
{
    /**
     * Scan the source of $function for calls that render $name or $file.
     *
     * @param ReflectionFunction $function The component function.
     * @param string $name The registered component name.
     * @param string $file The unit file path, accepted as a render() target.
     * @return array{scanned: bool, found: bool, dynamic: bool, positional: bool, keys: array<string, true>, variables: array<string, true>}
     *     `scanned` is false when the function source could not be read,
     *     `found` is false when no call targets the component, `dynamic` when a
     *     bindings array is unpacked, `positional` when data is passed by
     *     position (which render() rejects at runtime), `keys` are the named
     *     arguments, and `variables` every variable the function body uses.
     */
    public static function of(ReflectionFunction $function, string $name, string $file): array
    {
        $tokens = self::tokens($function);

        if ($tokens === null) {
            return self::empty(false);
        }

        return self::scan($tokens, $name, $file, $function, true);
    }

    /**
     * Scan a whole file, for a unit whose component function is not named after
     * the component (a page unit: `featuresPage()` rendering `'Features'`).
     *
     * @param string $file The unit file path.
     * @param string $name The registered component name.
     * @return array{scanned: bool, found: bool, dynamic: bool, positional: bool, keys: array<string, true>, variables: array<string, true>}
     */
    public static function inFile(string $file, string $name): array
    {
        if (!is_file($file)) {
            return self::empty(false);
        }

        $source = file_get_contents($file);

        if ($source === false) {
            return self::empty(false);
        }

        /** @var list<array{0: int, 1: string, 2: int}|string> $tokens */
        $tokens = token_get_all('<?php ' . $source);

        return self::scan($tokens, $name, $file, null, false);
    }

    /**
     * @return array{scanned: bool, found: bool, dynamic: bool, positional: bool, keys: array<string, true>, variables: array<string, true>}
     */
    private static function empty(bool $scanned): array
    {
        return [
            'scanned' => $scanned,
            'found' => false,
            'dynamic' => false,
            'positional' => false,
            'keys' => [],
            'variables' => [],
        ];
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @param ?ReflectionFunction $function The component function, when one was found.
     * @param bool $bodyOnly Whether to collect variables (only possible for a function).
     * @return array{scanned: bool, found: bool, dynamic: bool, positional: bool, keys: array<string, true>, variables: array<string, true>}
     */
    private static function scan(array $tokens, string $name, string $file, ?ReflectionFunction $function, bool $bodyOnly): array
    {
        $result = self::empty(true);

        $bodyStart = 0;

        if ($bodyOnly) {
            foreach ($tokens as $index => $token) {
                if ($token === '{') {
                    $bodyStart = $index;

                    break;
                }
            }
        }

        $count = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];

            if ($index > $bodyStart && is_array($token) && $token[0] === T_VARIABLE) {
                $result['variables'][substr($token[1], 1)] = true;
            }

            if (!self::isRenderCall($tokens, $index)) {
                continue;
            }

            $call = self::arguments($tokens, $index, $function, $file);

            if ($call === null || $call['source'] === null || !self::targets($call['source'], $name, $file, $function)) {
                continue;
            }

            $result['found'] = true;
            $result['dynamic'] = $result['dynamic'] || $call['dynamic'];
            $result['positional'] = $result['positional'] || $call['positional'];

            foreach ($call['keys'] as $key => $_) {
                $result['keys'][$key] = true;
            }
        }

        return $result;
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
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function isRenderCall(array $tokens, int $index): bool
    {
        $token = $tokens[$index];

        if (!is_array($token) || !in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
            return false;
        }

        $value = $token[1];
        $position = strrpos($value, '\\');
        $short = $position === false ? $value : substr($value, $position + 1);

        if (strtolower($short) !== 'render') {
            return false;
        }

        $previous = self::significant($tokens, $index, -1);

        if (
            is_array($previous)
            && in_array($previous[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST], true)
        ) {
            return false;
        }

        return self::significant($tokens, $index, 1) === '(';
    }

    /**
     * The named bindings of one render() call.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return array{source: ?string, keys: array<string, true>, dynamic: bool, positional: bool}|null
     */
    private static function arguments(array $tokens, int $index, ?ReflectionFunction $function, string $file): ?array
    {
        $count = count($tokens);
        $open = null;

        for ($cursor = $index + 1; $cursor < $count; $cursor++) {
            $token = $tokens[$cursor];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($token === '(') {
                $open = $cursor;
            }

            break;
        }

        if ($open === null) {
            return null;
        }

        $groups = [];
        $group = [];
        $depth = 0;
        $closed = false;

        for ($cursor = $open + 1; $cursor < $count; $cursor++) {
            $token = $tokens[$cursor];

            if (
                $token === '(' || $token === '[' || $token === '{'
                || is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)
            ) {
                $depth++;
            } elseif ($token === ')' && $depth === 0) {
                $groups[] = $group;
                $closed = true;

                break;
            } elseif ($token === ')' || $token === ']' || $token === '}') {
                // An interpolation or heredoc body can close more than it
                // opens: never let the depth go negative.
                $depth = max(0, $depth - 1);
            } elseif ($token === ',' && $depth === 0) {
                $groups[] = $group;
                $group = [];

                continue;
            }

            $group[] = $token;
        }

        if (!$closed) {
            return null;
        }

        $source = null;
        $keys = [];
        $dynamic = false;
        $positional = false;

        foreach ($groups as $position => $groupTokens) {
            $tokensOfArgument = self::trim($groupTokens);

            if ($tokensOfArgument === []) {
                continue;
            }

            $first = $tokensOfArgument[0];

            if (is_array($first) && $first[0] === T_ELLIPSIS) {
                $spread = self::spreadKeys($tokensOfArgument, $file);

                if ($spread === null) {
                    $dynamic = true;
                } else {
                    foreach ($spread as $key => $_) {
                        $keys[$key] = true;
                    }
                }

                continue;
            }

            if (
                is_array($first)
                // A named argument may use a reserved word (`class:`, `list:`),
                // which tokenizes as a keyword rather than T_STRING.
                && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $first[1]) === 1
                && ($tokensOfArgument[1] ?? null) === ':'
                && ($tokensOfArgument[2] ?? null) !== ':'
            ) {
                $keys[$first[1]] = true;

                continue;
            }

            if ($position === 0) {
                $source = self::literal($tokensOfArgument, $function, $file);

                continue;
            }

            $positional = true;
        }

        return ['source' => $source, 'keys' => $keys, 'dynamic' => $dynamic, 'positional' => $positional];
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function literal(array $tokens, ?ReflectionFunction $function, string $file): ?string
    {
        if (count($tokens) !== 1 || !is_array($tokens[0])) {
            return null;
        }

        if ($tokens[0][0] === T_CONSTANT_ENCAPSED_STRING) {
            return self::unquote($tokens[0][1]);
        }

        if ($tokens[0][0] === T_FILE) {
            $source = $function === null ? false : $function->getFileName();

            return $source === false ? $file : $source;
        }

        return null;
    }

    /**
     * The keys of an unpacked bindings helper: `...bindings()` is resolved when
     * the helper is a function in the unit file whose only return is an array
     * literal with string keys. Anything else (a variable, a computed array)
     * returns null and makes the call dynamic.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokensOfArgument
     * @return array<string, true>|null
     */
    private static function spreadKeys(array $tokensOfArgument, string $file): ?array
    {
        if (count($tokensOfArgument) !== 4) {
            return null;
        }

        $name = $tokensOfArgument[1];
        $open = $tokensOfArgument[2];
        $close = $tokensOfArgument[3];

        if (
            !is_array($name) || $name[0] !== T_STRING
            || $open !== '(' || $close !== ')'
        ) {
            return null;
        }

        $helper = FunctionFinder::of($name[1], $file);

        return $helper === null ? null : self::literalKeys($helper);
    }

    /**
     * The string keys of a function whose only return is an array literal: the
     * bindings of a `...bindings()` helper or of a prepare() hook, or null when
     * the keys are branch-dependent or computed.
     *
     * @param ReflectionFunction $function The bindings helper.
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

    private static function targets(string $source, string $name, string $file, ?ReflectionFunction $function): bool
    {
        if ($source === $name) {
            return true;
        }

        $candidate = realpath($source);

        if ($candidate === false) {
            return false;
        }

        $unit = realpath($file);

        if ($candidate === ($unit === false ? $file : $unit)) {
            return true;
        }

        $sourceFile = $function === null ? false : $function->getFileName();

        return $sourceFile !== false && $candidate === realpath($sourceFile);
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return list<array{0: int, 1: string, 2: int}|string>
     */
    private static function trim(array $tokens): array
    {
        $trimmed = [];

        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $trimmed[] = $token;
        }

        return $trimmed;
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
