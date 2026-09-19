<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

/**
 * One result of a contract check: an error fails the command, a warning only
 * with --strict, an info is a note about what could not be checked.
 *
 * @internal
 */
final class Finding
{
    private function __construct(
        public readonly string $level,
        public readonly string $message
    ) {
    }

    public static function error(string $message): self
    {
        return new self('error', $message);
    }

    public static function warning(string $message): self
    {
        return new self('warning', $message);
    }

    public static function info(string $message): self
    {
        return new self('info', $message);
    }
}
