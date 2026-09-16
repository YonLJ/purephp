<?php

declare(strict_types=1);

namespace Pure\Compile;

use Closure;
use LogicException;
use Pure\Core\Escaper;
use Pure\Core\Raw;
use Pure\Core\Slot;
use Pure\Core\SlotKind;
use Pure\Core\Tag;

/**
 * Generates a flat PHP renderer for a shape tree.
 *
 * Static markup is escaped once at compile time and emitted as literal string
 * chunks; slots become the only runtime work.
 *
 * @internal
 */
final class Compiler
{
    /** @var string[] */
    private array $lines = [];

    /** @var array<int, Closure> */
    private array $maps = [];

    private string $literal = '';

    private int $scope = 0;

    private function __construct()
    {
    }

    public static function compile(Tag $tree): Compiled
    {
        $compiler = new self();
        $compiler->emit('$out = \'\';');
        $compiler->compileTag($tree, '$v', '');
        $compiler->flushLiteral();
        $compiler->emit('return $out;');

        $source = "static function (array \$v, array \$maps): string {\n    " . implode("\n    ", $compiler->lines) . "\n}";

        $renderer = eval('return ' . $source . ';');
        if (!$renderer instanceof Closure) {
            throw new LogicException('failed to compile shape renderer.');
        }

        return new Compiled($renderer, $source, $compiler->maps);
    }

    private function compileTag(Tag $tag, string $dataVar, string $path): void
    {
        $export = $tag->export();
        $this->literal('<' . $export['tagName']);

        foreach ($export['attrs'] as $key => $value) {
            $slotPath = $path === '' ? (string)$key : $path . '.' . $key;
            if ($value instanceof Slot) {
                if ($value->kind !== SlotKind::Attr) {
                    throw new LogicException("only attribute slots are allowed in attribute position, got '{$value->kind->name}' for '{$slotPath}'.");
                }

                $this->expression(
                    '\Pure\Compile\Values::attrOpen(' . var_export((string)$key, true) . ', '
                    . $this->valueAccess($value, $dataVar, $slotPath) . ', '
                    . var_export($slotPath, true) . ')'
                );

                continue;
            }

            $this->literal(' ' . $key . '="' . Escaper::attr($value) . '"');
        }

        if ($export['selfClose']) {
            $this->literal(' />');

            return;
        }

        $this->literal('>');

        foreach ($export['children'] as $child) {
            if ($child instanceof Tag) {
                $this->compileTag($child, $dataVar, $path);

                continue;
            }

            if ($child instanceof Raw) {
                $this->literal((string)$child);

                continue;
            }

            if ($child instanceof Slot) {
                $this->compileSlot($child, $dataVar, $path);

                continue;
            }

            $this->literal(Escaper::text((string)$child));
        }

        $this->literal('</' . $export['tagName'] . '>');
    }

    private function compileSlot(Slot $slot, string $dataVar, string $path): void
    {
        $slotPath = $path === '' ? $slot->name : $path . '.' . $slot->name;

        switch ($slot->kind) {
            case SlotKind::Text:
                $this->expression($this->valueExpr('text', $slot, $dataVar, $slotPath));

                return;

            case SlotKind::Raw:
                $this->expression($this->valueExpr('raw', $slot, $dataVar, $slotPath));

                return;

            case SlotKind::Sub:
                $childVar = '$v' . (++$this->scope);
                $this->statement($childVar . ' = ' . $this->scopeExpr($slot, $this->valueAccess($slot, $dataVar, $slotPath), $slotPath, $dataVar) . ';');
                $this->compileTag($this->shapeOf($slot, $slotPath)->tree(), $childVar, $slotPath);

                return;

            case SlotKind::Each:
                $itemVar = '$item' . (++$this->scope);
                $childVar = '$v' . (++$this->scope);
                $this->statement('foreach (\Pure\Compile\Values::items(' . $this->valueAccess($slot, $dataVar, $slotPath) . ', ' . var_export($slotPath, true) . ') as ' . $itemVar . ') {');
                $this->emit($childVar . ' = ' . $this->scopeExpr($slot, $itemVar, $slotPath . '[]', $itemVar) . ';');
                $this->compileTag($this->shapeOf($slot, $slotPath)->tree(), $childVar, $slotPath . '[]');
                $this->flushLiteral();
                $this->emit('}');

                return;

            default:
                throw new LogicException("slot '{$slotPath}' is not supported in child position.");
        }
    }

    private function shapeOf(Slot $slot, string $slotPath): Shape
    {
        if ($slot->shape === null) {
            throw new LogicException("slot '{$slotPath}' is missing a shape.");
        }

        return $slot->shape;
    }

    /**
     * Expression producing the nested data scope of a sub/each slot.
     */
    private function scopeExpr(Slot $slot, string $value, string $slotPath, string $mapInput): string
    {
        if ($slot->map !== null) {
            $index = count($this->maps);
            $this->maps[] = $slot->map;
            $value = '($maps[' . $index . '])(' . $mapInput . ')';
        }

        return '\Pure\Compile\Values::sub(' . $value . ', ' . var_export($slotPath, true) . ')';
    }

    private function valueAccess(Slot $slot, string $dataVar, string $slotPath): string
    {
        $key = var_export($slot->name, true);

        if ($slot->required) {
            return '(\array_key_exists(' . $key . ', ' . $dataVar . ') ? ' . $dataVar . '[' . $key . '] : throw \Pure\Core\MissingSlotException::forPath(' . var_export($slotPath, true) . '))';
        }

        return '(' . $dataVar . '[' . $key . '] ?? ' . var_export($slot->default, true) . ')';
    }

    private function valueExpr(string $kind, Slot $slot, string $dataVar, string $slotPath): string
    {
        return '\Pure\Compile\Values::' . $kind . '(' . $this->valueAccess($slot, $dataVar, $slotPath) . ', ' . var_export($slotPath, true) . ')';
    }

    private function literal(string $text): void
    {
        $this->literal .= $text;
    }

    private function expression(string $expr): void
    {
        $this->flushLiteral();
        $this->emit('$out .= ' . $expr . ';');
    }

    private function statement(string $statement): void
    {
        $this->flushLiteral();
        $this->emit($statement);
    }

    private function flushLiteral(): void
    {
        if ($this->literal === '') {
            return;
        }

        $this->emit('$out .= ' . var_export($this->literal, true) . ';');
        $this->literal = '';
    }

    private function emit(string $line): void
    {
        $this->lines[] = $line;
    }
}
