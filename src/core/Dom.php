<?php declare(strict_types=1);
namespace Pure\Core;

abstract class Dom {
    protected string $tagName;

    protected array $attrs = [];

    protected array $children = [];

    abstract public function __toString(): string;
}

