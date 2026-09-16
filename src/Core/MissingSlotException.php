<?php

declare(strict_types=1);

namespace Pure\Core;

use RuntimeException;

final class MissingSlotException extends RuntimeException
{
    public static function forPath(string $path): self
    {
        return new self("slot '{$path}' is required but was not provided.");
    }
}
