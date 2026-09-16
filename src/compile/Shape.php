<?php

declare(strict_types=1);

namespace Pure\Compile;

use Pure\Core\Tag;

/**
 * A data-free shape tree that can be compiled into a flat renderer.
 *
 * Build shapes once per process (for example in a static variable inside a
 * component function) and render them per request with data.
 */
final class Shape
{
    private ?Compiled $compiled = null;

    public function __construct(private readonly Tag $tree)
    {
    }

    /** @param array<string, mixed> $data */
    public function __invoke(array $data): string
    {
        return ($this->compile())($data);
    }

    public function compile(): Compiled
    {
        return $this->compiled ??= Compiler::compile($this->tree);
    }

    public function id(): string
    {
        return $this->compile()->id();
    }

    /** @param array<string, mixed> $data */
    public function print(array $data): void
    {
        echo ($this->compile())($data);
    }

    /** @internal */
    public function tree(): Tag
    {
        return $this->tree;
    }
}
