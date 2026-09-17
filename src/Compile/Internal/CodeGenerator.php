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
        return self::fromSource(self::generate($tree), $index->id());
    }

    /**
     * Generate the renderer source without evaluating it.
     *
     * @internal
     *
     * @param Tag $tree The shape tree to compile.
     * @return string The generated PHP source.
     */
    public static function source(Tag $tree): string
    {
        return self::generate($tree);
    }

    /**
     * @param Tag $tree The shape tree to compile.
     * @return string The generated source.
     */
    private static function generate(Tag $tree): string
    {
        $generator = new self();

        $generator->emit('$out = \'\';');
        (new ShapeWalker($generator))->walk($tree);
        $generator->flushLiteral();
        $generator->emit('return $out;');

        return "static function (array \$v): string {\n    " . implode("\n    ", $generator->lines) . "\n}";
    }

    /**
     * Build a renderer from generated source.
     *
     * @internal
     *
     * @param string $source The generated PHP source.
     * @param string $id The shape fingerprint.
     * @return Renderer The compiled renderer.
     */
    public static function fromSource(string $source, string $id): Renderer
    {
        $closure = eval('return ' . $source . ';');
        if (!$closure instanceof Closure) {
            throw new LogicException('failed to compile shape renderer.');
        }

        return new Renderer($closure, $source, $id);
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
