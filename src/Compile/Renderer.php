<?php

declare(strict_types=1);

namespace Pure\Compile;

use Closure;
use Pure\Core\DevMode;
use Pure\Core\Suggestion;

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
     * @param list<string>|null $slots The root slot names the template reads,
     *     or null when the renderer was built without a manifest (the
     *     development guard then stays quiet). Every compiler path passes it.
     */
    public function __construct(
        private readonly Closure $closure,
        public readonly string $source,
        public readonly string $id,
        public readonly ?array $slots = null
    ) {
    }

    /**
     * Render the compiled shape with the given data.
     *
     * With the development guard on, data keys the template never reads are
     * reported once each: a misspelled binding fails here instead of rendering
     * as if the value were absent.
     *
     * @param array<int|string, mixed> $data The rendering data.
     * @return string The rendered output.
     */
    public function render(array $data): string
    {
        if ($this->slots !== null && (DevMode::$enabled ?? DevMode::resolve())) {
            $this->warnUnknownKeys($data);
        }

        return ($this->closure)($data);
    }

    /**
     * Render the shape with the given data and write the output to a file.
     *
     * The renderer carries no document header: pass one to prepend it, or use
     * Shape::save() to prepend the root tag's own header.
     *
     * @param string $path The file path to save to.
     * @param array<int|string, mixed> $data The rendering data.
     * @param string $header Optional document header to prepend.
     * @return int|false The number of bytes written, or false on failure.
     */
    public function save(string $path, array $data, string $header = ''): int|false
    {
        return file_put_contents($path, $header . $this->render($data));
    }

    /**
     * Report the data keys outside the root manifest, once per key and
     * renderer. Keys arrive from `render(...)` bindings or a hand-written data
     * array, so a typo is the common cause; a suggestion names the slot the
     * value was probably meant for.
     *
     * @param array<int|string, mixed> $data
     */
    private function warnUnknownKeys(array $data): void
    {
        $slots = $this->slots ?? [];
        $fresh = [];

        foreach ($data as $key => $_) {
            $key = (string)$key;

            if (in_array($key, $slots, true)) {
                continue;
            }

            if (DevMode::mark('data:' . $this->id . ':' . $key)) {
                $fresh[] = $key;
            }
        }

        if ($fresh === []) {
            return;
        }

        $reported = array_map(
            static function (string $key) use ($slots): string {
                $nearest = Suggestion::nearest($key, $slots);

                return $nearest === null ? "'{$key}'" : "'{$key}' (did you mean '{$nearest}'?)";
            },
            $fresh
        );

        DevMode::emit(
            'unknown data ' . (count($fresh) === 1 ? 'key' : 'keys') . ' ' . implode(', ', $reported)
            . '; the template reads: ' . implode(', ', $slots) . '.'
            . ' Extra data is ignored, so a misspelled key renders as if absent;'
            . ' disable this warning with Compile::guard(false).'
        );
    }
}
