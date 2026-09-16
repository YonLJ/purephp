<?php

declare(strict_types=1);

namespace Pure\Compile;

use Closure;

/**
 * A compiled shape renderer.
 */
final class Compiled
{
    /**
     * @param Closure(array<string, mixed>, array<int, Closure>): string $renderer
     * @param array<int, Closure> $maps
     */
    public function __construct(
        private readonly Closure $renderer,
        private readonly string $source,
        private readonly array $maps = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public function __invoke(array $data): string
    {
        return ($this->renderer)($data, $this->maps);
    }

    public function id(): string
    {
        return sha1($this->source);
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
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return false;
        }

        $result = fwrite($handle, $header . $this->__invoke($data));
        fclose($handle);

        return $result;
    }
}
