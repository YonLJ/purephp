<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Closure;
use LogicException;
use Pure\Compile\Renderer;
use Pure\Core\Tag;

/**
 * Generates the runtime renderer source: flat `$out .= ...;` statements.
 *
 * This is the hot path used by the in-process compiler and the on-disk cache;
 * TemplateGenerator writes the same renderer as a readable template for
 * artifacts.
 *
 * @internal
 */
final class CodeGenerator extends RendererGenerator
{
    /** @var list<string> */
    private array $lines = [];

    public static function compile(Tag $tree, ShapeIndex $index): Renderer
    {
        [$source, $maps] = self::generate($tree, $index);

        return self::fromSource($source, $index->id(), $maps);
    }

    /**
     * Generate the renderer source without evaluating it.
     *
     * @internal
     *
     * @param Tag $tree The shape tree to compile.
     * @param ShapeIndex $index The structure index of the tree.
     * @return string The generated PHP source.
     */
    public static function source(Tag $tree, ShapeIndex $index): string
    {
        return self::generate($tree, $index)[0];
    }

    /**
     * @param Tag $tree The shape tree to compile.
     * @param ShapeIndex $index The structure index of the tree.
     * @return array{string, array<int, Closure>} The generated source and the map closures.
     */
    private static function generate(Tag $tree, ShapeIndex $index): array
    {
        $generator = new self();
        self::prepare($generator, $index);

        $generator->emit('$out = \'\';');
        (new ShapeWalker($generator))->walk($tree);
        $generator->flushLiteral();
        $generator->emit('return $out;');

        $source = "static function (array \$v, array \$maps): string {\n    " . implode("\n    ", $generator->lines) . "\n}";

        return [$source, $generator->maps];
    }

    /**
     * Build a renderer from generated source, binding the given map closures.
     *
     * The source depends only on the tree structure, so a cached string can be
     * reused for a tree with the same fingerprint while the maps stay live.
     *
     * @internal
     *
     * @param string $source The generated PHP source.
     * @param string $id The shape fingerprint.
     * @param array<int, Closure> $maps
     * @return Renderer The compiled renderer.
     */
    public static function fromSource(string $source, string $id, array $maps): Renderer
    {
        $closure = eval('return ' . $source . ';');
        if (!$closure instanceof Closure) {
            throw new LogicException('failed to compile shape renderer.');
        }

        return new Renderer($closure, $source, $id, $maps);
    }

    protected function emitLiteral(string $text): void
    {
        $this->emit('$out .= ' . var_export($text, true) . ';');
    }

    protected function emitExpression(string $expression): void
    {
        $this->emit('$out .= ' . $expression . ';');
    }

    protected function emitStatement(string $statement): void
    {
        $this->emit($statement);
    }

    private function emit(string $line): void
    {
        $this->lines[] = $line;
    }
}
