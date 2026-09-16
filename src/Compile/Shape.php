<?php

declare(strict_types=1);

namespace Pure\Compile;

use Pure\Compile\Internal\ShapeIndex;
use Pure\Core\ShapeContract;
use Pure\Core\Tag;

/**
 * A data-free shape tree that can be compiled into a flat renderer.
 *
 * Build shapes once per process (for example in a static variable inside a
 * component function) and render them per request with data.
 */
final class Shape implements ShapeContract
{
    private ?Renderer $renderer = null;

    private int $generation = -1;

    public function __construct(private readonly Tag $tree)
    {
    }

    /** @param array<string, mixed> $data */
    public function __invoke(array $data): string
    {
        return ($this->compile())($data);
    }

    /**
     * Compile the live tree, memoized per generation.
     *
     * The structure index is rebuilt from the live tree on every compile, so
     * the id and the generated (or cached) code always describe the same tree
     * state, even if the tree was mutated after a previous id() call.
     */
    public function compile(): Renderer
    {
        $generation = Compile::generation();
        if ($this->renderer === null || $this->generation !== $generation) {
            $this->renderer = Compile::renderer($this->tree);
            $this->generation = $generation;
        }

        return $this->renderer;
    }

    /**
     * Structure fingerprint of the current tree, available without compiling.
     *
     * Computed from the live tree on every call; a mutation is therefore
     * reflected here immediately, while an already compiled renderer (and its
     * cache file) keeps describing the tree state it was compiled from until
     * Compile::flush() is called.
     */
    public function id(): string
    {
        return ShapeIndex::of($this->tree)->id();
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
