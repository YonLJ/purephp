<?php

declare(strict_types=1);

namespace Pure\Compile;

use Closure;
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

    /** @var array{id: string, maps: array<string, Closure>}|null */
    private ?array $index = null;

    private int $generation = -1;

    public function __construct(private readonly Tag $tree)
    {
    }

    /** @param array<string, mixed> $data */
    public function __invoke(array $data): string
    {
        return ($this->compile())($data);
    }

    public function compile(): Renderer
    {
        $generation = Compile::generation();
        if ($this->renderer === null || $this->generation !== $generation) {
            $this->renderer = Compile::renderer($this->tree, $this->index());
            $this->generation = $generation;
        }

        return $this->renderer;
    }

    /** Structure fingerprint, available without compiling the shape. */
    public function id(): string
    {
        return $this->index()['id'];
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

    /** @return array{id: string, maps: array<string, Closure>} */
    private function index(): array
    {
        return $this->index ??= ShapeIndex::of($this->tree);
    }
}
