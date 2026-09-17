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
     * @param string $source Generated PHP source, for debugging.
     * @param string $id Structure fingerprint shared by the shape and its cached renderer.
     * @param array<int, Closure> $maps
     */
    public function __construct(
        private readonly Closure $closure,
        public readonly string $source,
        public readonly string $id,
        private readonly array $maps = [],
    ) {
    }

    /**
     * Render the compiled shape with the given data.
     *
     * @param array<string, mixed> $data The rendering data.
     * @return string The rendered output.
     */
    public function render(array $data): string
    {
        return ($this->closure)($data, $this->maps);
    }

    /**
     * Render the shape with the given data and write the output to a file.
     *
     * @param string $path The file path to save to.
     * @param array<string, mixed> $data The rendering data.
     * @param string $header Optional document header to prepend.
     * @return int|false The number of bytes written, or false on failure.
     */
    public function save(string $path, array $data, string $header = ''): int|false
    {
        return file_put_contents($path, $header . $this->render($data));
    }
}
