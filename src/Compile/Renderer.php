<?php

declare(strict_types=1);

namespace Pure\Compile;

use Closure;

/**
 * A compiled shape renderer.
 */
final class Renderer
{
    /**
     * @param Closure(array<string, mixed>, array<int, Closure>): string $closure
     * @param array<int, Closure> $maps
     */
    public function __construct(
        private readonly Closure $closure,
        private readonly string $source,
        private readonly string $id,
        private readonly array $maps = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public function __invoke(array $data): string
    {
        return ($this->closure)($data, $this->maps);
    }

    /** Structure fingerprint shared by the shape and its cached renderer. */
    public function id(): string
    {
        return $this->id;
    }

    /** Generated PHP source, for debugging. */
    public function source(): string
    {
        return $this->source;
    }

    /** @param array<string, mixed> $data */
    public function print(array $data): void
    {
        echo $this->__invoke($data);
    }

    /** @param array<string, mixed> $data */
    public function save(string $path, array $data, string $header = ''): int|false
    {
        return file_put_contents($path, $header . $this->__invoke($data));
    }
}
