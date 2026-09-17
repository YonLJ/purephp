<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Closure;
use LogicException;
use Pure\Compile\CompileException;
use ReflectionFunction;

/**
 * Copies a map closure into artifact source text.
 *
 * The closure is reproduced from the file that defines it: its source text is
 * copied verbatim into a namespace block that repeats the defining file's
 * namespace and the imports the closure actually uses, so every name resolves
 * exactly as it did in the original file. Closures whose behaviour depends on
 * context that text cannot carry (captured variables, `$this`, `__DIR__`,
 * `self`) are rejected with the slot path.
 *
 * @internal
 */
final class ClosureSource
{
    /**
     * @param list<string> $imports The used `use` statements of the defining section.
     */
    private function __construct(
        public readonly ?string $namespace,
        public readonly array $imports,
        public readonly string $code,
    ) {
    }

    /**
     * @param Closure $map The map closure to copy.
     * @param string $slotPath The slot path, for error messages.
     * @return self The copyable closure source.
     */
    public static function of(Closure $map, string $slotPath): self
    {
        $reflection = new ReflectionFunction($map);

        if ($reflection->getClosureThis() !== null) {
            throw CompileException::mapInArtifact($slotPath, self::origin($reflection) . ' is bound to an object');
        }

        $captured = $reflection->getClosureUsedVariables();
        if ($captured !== []) {
            throw CompileException::mapInArtifact($slotPath, self::origin($reflection) . ' captures ' . self::variables($captured));
        }

        if (!str_contains($reflection->getName(), '{closure')) {
            return new self(null, [], '\\Closure::fromCallable(' . var_export($reflection->getName(), true) . ')');
        }

        return self::locate($reflection, $slotPath);
    }

    private static function locate(ReflectionFunction $reflection, string $slotPath): self
    {
        $file = $reflection->getFileName();
        $contents = $file === false ? false : @file_get_contents($file);

        if ($contents === false) {
            throw CompileException::mapInArtifact($slotPath, 'its source file is not readable');
        }

        $tokens = token_get_all($contents);
        $lines = self::lines($tokens);
        $start = self::startToken($tokens, $lines, $reflection, $slotPath);
        $end = self::endToken($tokens, $start);
        $endLine = $lines[$end] + substr_count(self::text($tokens[$end]), "\n");

        if ($endLine !== $reflection->getEndLine()) {
            throw CompileException::mapInArtifact($slotPath, self::origin($reflection) . ' could not be isolated reliably');
        }

        self::assertContextFree($tokens, $start, $end, $slotPath);

        $depths = self::depths($tokens);
        $section = self::section($tokens, $depths, $start);
        $imports = self::usedImports($tokens, self::imports($tokens, $depths, $section), $start, $end);

        return new self($section['namespace'], $imports, self::slice($tokens, $start, $end));
    }

    /**
     * The index of the first token of the closure: its `static` keyword when
     * present, otherwise the `fn` / `function` keyword.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @param list<int> $lines
     */
    private static function startToken(array $tokens, array $lines, ReflectionFunction $reflection, string $slotPath): int
    {
        $startLine = $reflection->getStartLine();
        $candidates = [];

        foreach ($tokens as $index => $token) {
            if (!is_array($token) || ($token[0] !== T_FN && $token[0] !== T_FUNCTION)) {
                continue;
            }

            $next = self::significantAfter($tokens, $index + 1);
            $text = $next === null ? '' : self::text($tokens[$next]);

            if ($text !== '(' && $text !== '&') {
                continue; // A named function declaration or a `use function` import.
            }

            $previous = self::significantBefore($tokens, $index - 1);
            $begin = $previous !== null && is_array($tokens[$previous]) && $tokens[$previous][0] === T_STATIC ? $previous : $index;

            if ($lines[$begin] !== $startLine && $lines[$index] !== $startLine) {
                continue;
            }

            $candidates[] = $begin;
        }

        if ($candidates === []) {
            throw CompileException::mapInArtifact($slotPath, self::origin($reflection) . ' could not be found in its source file');
        }

        if (count($candidates) > 1) {
            throw CompileException::mapInArtifact($slotPath, self::origin($reflection) . ' shares a line with another closure; put it on its own line');
        }

        return $candidates[0];
    }

    /**
     * The index of the last token of the closure body (`}` or the last token
     * of the expression returned by an arrow function).
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function endToken(array $tokens, int $start): int
    {
        $keyword = is_array($tokens[$start]) && $tokens[$start][0] === T_STATIC
            ? self::significantAfter($tokens, $start + 1)
            : $start;

        if ($keyword === null) {
            throw new LogicException('closure keyword is missing.');
        }

        $parameters = self::significantAfter($tokens, $keyword + 1);

        if ($parameters === null || self::text($tokens[$parameters]) !== '(') {
            throw new LogicException('closure parameters are missing.');
        }

        $arrowFunction = is_array($tokens[$keyword]) && $tokens[$keyword][0] === T_FN;
        $after = self::balanced($tokens, $parameters, '(', ')');

        if (!$arrowFunction) {
            $use = self::significantAfter($tokens, $after);

            if ($use !== null && is_array($tokens[$use]) && $tokens[$use][0] === T_USE) {
                $open = self::significantAfter($tokens, $use + 1);

                if ($open === null) {
                    throw new LogicException('closure use clause is malformed.');
                }

                $after = self::balanced($tokens, $open, '(', ')');
            }
        }

        // Skip the optional return type up to the body token: `=>` for arrow
        // functions, `{` for closures.
        $depth = 0;

        for ($cursor = $after; ; $cursor++) {
            $next = self::significantAfter($tokens, $cursor);

            if ($next === null) {
                throw new LogicException('closure body is missing.');
            }

            $token = $tokens[$next];

            if (self::interpolation($token)) {
                $depth++;

                continue;
            }

            $text = self::text($token);

            if ($text === '(' || $text === '[' || $text === '{') {
                if ($depth === 0 && $text === '{') {
                    if ($arrowFunction) {
                        throw new LogicException('arrow function body is missing.');
                    }

                    return self::balanced($tokens, $next, '{', '}') - 1;
                }

                $depth++;
            } elseif ($text === ')' || $text === ']' || $text === '}') {
                if ($depth === 0) {
                    throw new LogicException('closure signature is unbalanced.');
                }

                $depth--;
            } elseif ($depth === 0 && ($text === ';' || $text === ',')) {
                throw new LogicException('closure body is missing.');
            } elseif ($depth === 0 && $arrowFunction && is_array($token) && $token[0] === T_DOUBLE_ARROW) {
                return self::bodyEnd($tokens, $next + 1);
            }
        }
    }

    /**
     * The last token of an arrow function expression: it ends at the first
     * token at nesting depth zero that cannot continue the expression.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function bodyEnd(array $tokens, int $from): int
    {
        $depth = 0;
        $last = null;
        $count = count($tokens);

        for ($index = $from; $index < $count; $index++) {
            $token = $tokens[$index];

            if (self::interpolation($token)) {
                $depth++;
                $last = $index;

                continue;
            }

            $text = self::text($token);

            if ($depth === 0 && in_array($text, [',', ';', ')', ']', '}'], true)) {
                break;
            }

            if ($text === '(' || $text === '[' || $text === '{') {
                $depth++;
            } elseif ($text === ')' || $text === ']' || $text === '}') {
                $depth--;
            }

            if (self::significant($token)) {
                $last = $index;
            }
        }

        if ($last === null) {
            throw new LogicException('arrow function body is empty.');
        }

        return $last;
    }

    /**
     * The index after the close token matching the open token at $open.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function balanced(array $tokens, int $open, string $openText, string $closeText): int
    {
        $depth = 0;
        $count = count($tokens);

        for ($index = $open; $index < $count; $index++) {
            if (self::interpolation($tokens[$index])) {
                $depth++;

                continue;
            }

            $text = self::text($tokens[$index]);

            if ($text === $openText) {
                $depth++;
            } elseif ($text === $closeText) {
                $depth--;

                if ($depth === 0) {
                    return $index + 1;
                }
            }
        }

        throw new LogicException("unbalanced '{$openText}' in closure source.");
    }

    /**
     * Reject closures whose behaviour cannot be carried by the copied text.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function assertContextFree(array $tokens, int $start, int $end, string $slotPath): void
    {
        for ($index = $start; $index <= $end; $index++) {
            $token = $tokens[$index];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_STATIC) {
                $next = self::significantAfter($tokens, $index + 1);

                if ($next !== null && self::text($tokens[$next]) === '::') {
                    throw CompileException::mapInArtifact($slotPath, 'it uses static::, which resolves against the file it is defined in');
                }

                continue;
            }

            $contextual = ($token[0] === T_VARIABLE && $token[1] === '$this')
                || ($token[0] === T_STRING && in_array(strtolower($token[1]), ['self', 'parent'], true))
                || in_array($token[0], [T_FILE, T_DIR, T_LINE, T_CLASS_C, T_TRAIT_C], true);

            if ($contextual) {
                throw CompileException::mapInArtifact($slotPath, "it uses {$token[1]}, which resolves against the file it is defined in");
            }
        }
    }

    /**
     * The namespace section that contains the closure: its name, and the
     * begin and end (exclusive) token indices of its body.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @param list<int> $depths
     * @return array{namespace: ?string, begin: int, end: int}
     */
    private static function section(array $tokens, array $depths, int $start): array
    {
        $declaration = null;

        foreach ($tokens as $index => $token) {
            if ($index >= $start) {
                break;
            }

            if (is_array($token) && $token[0] === T_NAMESPACE && $depths[$index] === 0) {
                $declaration = $index;
            }
        }

        if ($declaration === null) {
            return ['namespace' => null, 'begin' => 0, 'end' => count($tokens)];
        }

        $name = null;
        $delimiter = self::significantAfter($tokens, $declaration + 1);

        if ($delimiter !== null && is_array($tokens[$delimiter]) && in_array($tokens[$delimiter][0], [T_STRING, T_NAME_QUALIFIED], true)) {
            $name = $tokens[$delimiter][1];
            $delimiter = self::significantAfter($tokens, $delimiter + 1);
        }

        if ($delimiter === null) {
            return ['namespace' => null, 'begin' => 0, 'end' => count($tokens)];
        }

        if (self::text($tokens[$delimiter]) === ';') {
            $end = count($tokens);

            foreach ($tokens as $index => $token) {
                if ($index <= $delimiter) {
                    continue;
                }

                if (is_array($token) && $token[0] === T_NAMESPACE && $depths[$index] === 0) {
                    $end = $index;

                    break;
                }
            }

            return ['namespace' => $name, 'begin' => $delimiter + 1, 'end' => $end];
        }

        return ['namespace' => $name, 'begin' => $delimiter + 1, 'end' => self::balanced($tokens, $delimiter, '{', '}') - 1];
    }

    /**
     * The `use` statements of the section at its statement depth, so imports
     * keep resolving names inside the copied closure.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @param list<int> $depths
     * @param array{namespace: ?string, begin: int, end: int} $section
     * @return list<string>
     */
    private static function imports(array $tokens, array $depths, array $section): array
    {
        $imports = [];
        $depth = $section['begin'] < $section['end'] ? $depths[$section['begin']] : 0;

        for ($index = $section['begin']; $index < $section['end']; $index++) {
            $token = $tokens[$index];

            if (!is_array($token) || $token[0] !== T_USE || $depths[$index] !== $depth) {
                continue;
            }

            $previous = self::significantBefore($tokens, $index - 1);

            if ($previous !== null && self::text($tokens[$previous]) === ')') {
                continue; // A `use (...)` clause of a closure.
            }

            $imports[] = self::statement($tokens, $index);
        }

        return $imports;
    }

    /**
     * Keep only the imports the copied snippet references: a snippet usually
     * uses a handful of the defining file's imports, and copying all of them
     * buries the closure in context it does not need.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @param list<string> $imports
     * @return list<string>
     */
    private static function usedImports(array $tokens, array $imports, int $start, int $end): array
    {
        if ($imports === []) {
            return [];
        }

        $used = self::names($tokens, $start, $end);
        $kept = [];

        foreach ($imports as $import) {
            foreach (self::imported($import) as $name) {
                if (in_array($name, $used, true)) {
                    $kept[] = $import;

                    break;
                }
            }
        }

        return $kept;
    }

    /**
     * The names a `use` statement makes available, by alias when one is given.
     *
     * @return list<string>
     */
    private static function imported(string $import): array
    {
        $names = [];
        $pending = null;
        $alias = false;

        foreach (token_get_all('<?php ' . $import) as $token) {
            if (!is_array($token)) {
                if ($token === ',' || $token === ';' || $token === '}') {
                    if ($pending !== null) {
                        $names[] = $pending;
                        $pending = null;
                    }

                    $alias = false;
                }

                continue;
            }

            if ($token[0] === T_AS) {
                $alias = true;
                $pending = null;

                continue;
            }

            if ($token[0] === T_STRING || $token[0] === T_NAME_QUALIFIED) {
                $position = strrpos($token[1], '\\');
                $pending = $alias || $position === false ? $token[1] : substr($token[1], $position + 1);
            }
        }

        if ($pending !== null) {
            $names[] = $pending;
        }

        return $names;
    }

    /**
     * The class-like and function-like names the snippet refers to.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return list<string>
     */
    private static function names(array $tokens, int $start, int $end): array
    {
        $names = [];

        for ($index = $start; $index <= $end; $index++) {
            $token = $tokens[$index];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_STRING) {
                $names[] = $token[1];

                continue;
            }

            if ($token[0] === T_NAME_QUALIFIED) {
                $position = strpos($token[1], '\\');
                $names[] = $position === false ? $token[1] : substr($token[1], 0, $position);
            }
        }

        return $names;
    }

    /**
     * The full `use ...;` statement starting at $start.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function statement(array $tokens, int $start): string
    {
        $count = count($tokens);

        for ($index = $start; $index < $count; $index++) {
            if (self::text($tokens[$index]) === ';') {
                return trim(self::slice($tokens, $start, $index));
            }
        }

        return trim(self::slice($tokens, $start, $count - 1));
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function slice(array $tokens, int $start, int $end): string
    {
        $text = '';

        for ($index = $start; $index <= $end; $index++) {
            $text .= self::text($tokens[$index]);
        }

        return $text;
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return list<int> The start line of every token.
     */
    private static function lines(array $tokens): array
    {
        $lines = [];
        $line = 1;

        foreach ($tokens as $token) {
            $lines[] = $line;
            $line += substr_count(self::text($token), "\n");
        }

        return $lines;
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return list<int> The brace and parenthesis depth before every token.
     */
    private static function depths(array $tokens): array
    {
        $depths = [];
        $depth = 0;

        foreach ($tokens as $token) {
            $depths[] = $depth;
            $text = self::text($token);

            if ($text === '{' || $text === '(' || $text === '[' || self::interpolation($token)) {
                $depth++;
            } elseif ($text === '}' || $text === ')' || $text === ']') {
                $depth--;
            }
        }

        return $depths;
    }

    /**
     * @param array{0: int, 1: string, 2: int}|string $token
     */
    private static function interpolation(array|string $token): bool
    {
        return is_array($token) && ($token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES);
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function significantAfter(array $tokens, int $from): ?int
    {
        $count = count($tokens);

        for ($index = $from; $index < $count; $index++) {
            if (self::significant($tokens[$index])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function significantBefore(array $tokens, int $from): ?int
    {
        for ($index = $from; $index >= 0; $index--) {
            if (self::significant($tokens[$index])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array{0: int, 1: string, 2: int}|string $token
     */
    private static function significant(array|string $token): bool
    {
        return !is_array($token) || ($token[0] !== T_WHITESPACE && $token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT);
    }

    /**
     * @param array{0: int, 1: string, 2: int}|string $token
     */
    private static function text(array|string $token): string
    {
        return is_array($token) ? $token[1] : $token;
    }

    /**
     * @param array<string, mixed> $captured
     */
    private static function variables(array $captured): string
    {
        return implode(', ', array_map(static fn (string $name): string => '$' . $name, array_keys($captured)));
    }

    private static function origin(ReflectionFunction $reflection): string
    {
        return $reflection->getFileName() === false
            ? 'the closure'
            : $reflection->getFileName() . ':' . $reflection->getStartLine();
    }
}
