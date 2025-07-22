<?php

declare(strict_types=1);

namespace Pure\Core;

abstract class Dom
{
    protected string $tagName;

    /** @var array<string, string> */
    protected array $attrs = [];

    /** @var array<int, mixed> */
    protected array $children = [];

    abstract public function __toString(): string;
}
