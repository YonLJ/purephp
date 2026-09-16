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
use WeakMap;

/**
 * Generates a flat PHP renderer by consuming the shared shape traversal.
 *
 * Static markup is escaped once at compile time and emitted as literal string
 * chunks; subtrees without slots are folded into single literals through the
 * shared string renderer, so only slots remain as runtime work.
 *
 * @internal
 */
final class CodeGenerator implements ShapeVisitor
{
    /** @var string[] */
    private array $lines = [];

    /** @var array<int, Closure> */
    private array $maps = [];

    /** @var array<string, int> */
    private array $mapIndex = [];

    /** @var list<array{slot: Slot, slotPath: string, mapKey: ?string}> */
    private array $slotStack = [];

    /** @var list<array{itemVar: string, childVar: string, branchOpen: bool}> */
    private array $eachAnyStack = [];

    /** @var list<string> */
    private array $dataStack = ['$v'];

    /** @var WeakMap<Tag, bool>|null */
    private ?WeakMap $slotCache = null;

    private string $literal = '';

    private int $scope = 0;

    private function __construct()
    {
    }

    public static function compile(Tag $tree, ShapeIndex $index): Renderer
    {
        $generator = new self();
        $generator->maps = array_values($index->maps());
        foreach (array_keys($index->maps()) as $position => $key) {
            $generator->mapIndex[$key] = $position;
        }

        $generator->emit('$out = \'\';');
        (new ShapeWalker($generator))->walk($tree);
        $generator->flushLiteral();
        $generator->emit('return $out;');

        $source = "static function (array \$v, array \$maps): string {\n    " . implode("\n    ", $generator->lines) . "\n}";

        $closure = eval('return ' . $source . ';');
        if (!$closure instanceof Closure) {
            throw new LogicException('failed to compile shape renderer.');
        }

        return new Renderer($closure, $source, $index->id(), $generator->maps);
    }

    /**
     * @param array{tagName: string, selfClose: bool, attrs: array<string, string|Slot>, children: array<int, mixed>} $export
     */
    public function tagOpen(Tag $tag, string $path, array $export): bool
    {
        if (!$this->hasSlots($tag)) {
            // Slot-free subtrees are static markup; the string renderer shares
            // the escaping implementation, so folding is byte-identical.
            $this->literal($tag->render());

            return false;
        }

        $this->literal('<' . $export['tagName']);

        return true;
    }

    public function attribute(string $key, string|Slot $value, string $slotPath): void
    {
        if ($value instanceof Slot) {
            if ($value->kind !== SlotKind::Attr) {
                throw CompileException::slotInAttributePosition($value->kind, $slotPath);
            }

            $this->expression(
                '\Pure\Compile\SlotRuntime::attrOpen(' . var_export($key, true) . ', '
                . $this->valueAccess($value, $this->data(), $slotPath) . ', '
                . var_export($slotPath, true) . ')'
            );

            return;
        }

        $this->literal(Escaper::attribute($key, $value));
    }

    public function tagSelfClose(): void
    {
        $this->literal(' />');
    }

    public function contentStart(): void
    {
        $this->literal('>');
    }

    public function tagClose(string $tagName): void
    {
        $this->literal('</' . $tagName . '>');
    }

    public function text(string $text): void
    {
        $this->literal(Escaper::text($text));
    }

    public function raw(Raw $raw): void
    {
        $this->literal((string)$raw);
    }

    public function slotEnter(Slot $slot, string $slotPath, ?string $mapKey): void
    {
        $this->slotStack[] = ['slot' => $slot, 'slotPath' => $slotPath, 'mapKey' => $mapKey];

        if ($this->enterValueSlot($slot, $slotPath)) {
            return;
        }

        $this->enterScopeSlot($slot, $slotPath, $mapKey);
    }

    public function slotBranch(string|int $label): void
    {
        $slot = $this->currentContext()['slot'];

        if ($slot->kind === SlotKind::If) {
            if ($label === 1) {
                $this->statement('} else {');
            }

            return;
        }

        if ($slot->kind !== SlotKind::EachAny) {
            throw new LogicException("unexpected branch event for slot kind '{$slot->kind->name}'.");
        }

        $context = array_pop($this->eachAnyStack);
        if ($context === null) {
            throw new LogicException('eachAny branch without an open list.');
        }

        if ($context['branchOpen']) {
            $this->statement('break;');
            array_pop($this->dataStack);
        }

        $current = $this->currentContext();
        $this->statement('case ' . var_export((string)$label, true) . ':');
        $this->statement($context['childVar'] . ' = ' . $this->scopeExpr($context['itemVar'], $current['slotPath'] . '[]', $context['itemVar'], $this->mapExpression($current['mapKey'])) . ';');
        $this->dataStack[] = $context['childVar'];
        $context['branchOpen'] = true;
        $this->eachAnyStack[] = $context;
    }

    public function slotLeave(Slot $slot, string $slotPath): void
    {
        switch ($slot->kind) {
            case SlotKind::Text:
            case SlotKind::Attr:
            case SlotKind::Raw:
                break;
            case SlotKind::Sub:
                array_pop($this->dataStack);

                break;
            case SlotKind::Each:
                $this->statement('}');
                array_pop($this->dataStack);

                break;
            case SlotKind::If:
                $this->statement('}');

                break;
            case SlotKind::EachAny:
                $context = array_pop($this->eachAnyStack);
                if ($context !== null && $context['branchOpen']) {
                    $this->statement('break;');
                    array_pop($this->dataStack);
                }
                $this->statement('}');
                $this->statement('}');

                break;
        }

        array_pop($this->slotStack);
    }

    private function enterValueSlot(Slot $slot, string $slotPath): bool
    {
        switch ($slot->kind) {
            case SlotKind::Text:
                $this->expression($this->valueExpr('text', $slot, $this->data(), $slotPath));

                return true;
            case SlotKind::Raw:
                $this->expression($this->valueExpr('raw', $slot, $this->data(), $slotPath));

                return true;
            case SlotKind::Attr:
                throw CompileException::attributeSlotInChildPosition($slotPath);
            default:
                return false;
        }
    }

    private function enterScopeSlot(Slot $slot, string $slotPath, ?string $mapKey): void
    {
        switch ($slot->kind) {
            case SlotKind::Sub:
                $childVar = '$v' . (++$this->scope);
                $this->statement($childVar . ' = ' . $this->scopeExpr($this->valueAccess($slot, $this->data(), $slotPath), $slotPath, $this->data(), $this->mapExpression($mapKey)) . ';');
                $this->dataStack[] = $childVar;

                return;
            case SlotKind::Each:
                $itemVar = '$item' . (++$this->scope);
                $childVar = '$v' . (++$this->scope);
                $this->statement('foreach (\Pure\Compile\SlotRuntime::items(' . $this->valueAccess($slot, $this->data(), $slotPath) . ', ' . var_export($slotPath, true) . ') as ' . $itemVar . ') {');
                $this->emit($childVar . ' = ' . $this->scopeExpr($itemVar, $slotPath . '[]', $itemVar, $this->mapExpression($mapKey)) . ';');
                $this->dataStack[] = $childVar;

                return;
            case SlotKind::If:
                $this->statement('if ((bool)' . $this->conditionExpr($slot, $this->data()) . ') {');

                return;
            case SlotKind::EachAny:
                $itemVar = '$item' . (++$this->scope);
                $childVar = '$v' . (++$this->scope);
                $kindVar = '$kind' . $this->scope;
                $kinds = [];
                foreach (array_keys($slot->variants) as $kind) {
                    $kinds[] = var_export((string)$kind, true);
                }

                $this->statement('foreach (\Pure\Compile\SlotRuntime::items(' . $this->valueAccess($slot, $this->data(), $slotPath) . ', ' . var_export($slotPath, true) . ') as ' . $itemVar . ') {');
                $this->statement($kindVar . ' = \Pure\Compile\SlotRuntime::kind(' . $itemVar . ', ' . var_export($slot->kindKey ?? 'kind', true) . ', ' . var_export($slotPath . '[]', true) . ', [' . implode(', ', $kinds) . ']);');
                $this->statement('switch (' . $kindVar . ') {');
                $this->eachAnyStack[] = ['itemVar' => $itemVar, 'childVar' => $childVar, 'branchOpen' => false];

                return;
            default:
                throw new LogicException("slot kind '{$slot->kind->name}' is not supported in child position.");
        }
    }

    /** @return array{slot: Slot, slotPath: string, mapKey: ?string} */
    private function currentContext(): array
    {
        $context = end($this->slotStack);
        if ($context === false) {
            throw new LogicException('slot event outside of a slot.');
        }

        return $context;
    }

    private function data(): string
    {
        $data = end($this->dataStack);
        if ($data === false) {
            throw new LogicException('missing data scope.');
        }

        return $data;
    }

    /**
     * Expression producing the nested data scope of a sub/each slot.
     */
    private function scopeExpr(string $value, string $scopePath, string $mapInput, ?string $mapExpression): string
    {
        if ($mapExpression !== null) {
            $value = $mapExpression . '(' . $mapInput . ')';
        }

        return '\Pure\Compile\SlotRuntime::sub(' . $value . ', ' . var_export($scopePath, true) . ')';
    }

    private function mapExpression(?string $mapKey): ?string
    {
        if ($mapKey === null) {
            return null;
        }

        if (!isset($this->mapIndex[$mapKey])) {
            throw new LogicException("map '{$mapKey}' is missing from the shape index.");
        }

        return '($maps[' . $this->mapIndex[$mapKey] . '])';
    }

    private function conditionExpr(Slot $slot, string $dataVar): string
    {
        return '(' . $dataVar . '[' . var_export($slot->name, true) . '] ?? ' . var_export($slot->default, true) . ')';
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
        return '\Pure\Compile\SlotRuntime::' . $kind . '(' . $this->valueAccess($slot, $dataVar, $slotPath) . ', ' . var_export($slotPath, true) . ')';
    }

    private function hasSlots(Tag $tag): bool
    {
        /** @var WeakMap<Tag, bool> $cache */
        $cache = $this->slotCache ?? new WeakMap();
        $this->slotCache = $cache;

        if (isset($cache[$tag])) {
            return $cache[$tag];
        }

        $export = $tag->export();
        $hasSlots = false;

        foreach ($export['attrs'] as $value) {
            if ($value instanceof Slot) {
                $hasSlots = true;

                break;
            }
        }

        if (!$hasSlots) {
            foreach ($export['children'] as $child) {
                if ($child instanceof Slot || ($child instanceof Tag && $this->hasSlots($child))) {
                    $hasSlots = true;

                    break;
                }
            }
        }

        $cache[$tag] = $hasSlots;

        return $hasSlots;
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
