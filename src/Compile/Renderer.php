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
     * @internal Renderers are created by the compiler, not by user code.
     *
     * @param Closure(array<int|string, mixed>): string $closure
     * @param string $source Generated PHP source, for debugging; empty for
     *     artifacts, whose file is the source.
     * @param string $id Structure fingerprint shared by the shape and its cached renderer.
     * @param string $header Document header captured at compile time; empty for
     *     renderers compiled at runtime, where Shape::save() passes the header
     *     of the root tag explicitly.
     */
    public function __construct(
        private readonly Closure $closure,
        public readonly string $source,
        public readonly string $id,
        public readonly string $header = '',
    ) {
    }

    /**
     * Render the compiled shape with the given data.
     *
     * @param array<int|string, mixed> $data The rendering data.
     * @return string The rendered output.
     */
    public function render(array $data): string
    {
        return ($this->closure)($data);
    }

    /**
     * Render the shape with the given data and write the output to a file.
     *
     * @param string $path The file path to save to.
     * @param array<int|string, mixed> $data The rendering data.
     * @param string|null $header Optional document header to prepend; null uses
     *     the header captured at compile time.
     * @return int|false The number of bytes written, or false on failure.
     */
    public function save(string $path, array $data, ?string $header = null): int|false
    {
        return file_put_contents($path, ($header ?? $this->header) . $this->render($data));
    }
}
