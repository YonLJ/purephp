<?php

declare(strict_types=1);

namespace Pure\Core;

final class Raw
{
    public function __construct(
        public readonly RawType $type,
        private readonly string $content,
    ) {
    }

    public function __toString(): string
    {
        return $this->content;
    }

    /**
     * Return the raw content as an associative array for serialization.
     *
     * @return array{type: string, content: string}
     */
    public function toJSON(): array
    {
        return [
            'type' => $this->type->name,
            'content' => $this->content,
        ];
    }
}
