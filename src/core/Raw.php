<?php declare(strict_types=1);
namespace Pure\Core;

enum RawType
{
    case HTML;
    case XML;
}

class Raw {
    public readonly RawType $type;

    private string $content;

    public function __construct(RawType $type, string $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function toJSON(): array
    {
        $type = $this->type === RawType::HTML ? 'HTML' : 'XML';
        return [
            'type' => $type,
            'content' => $this->content
        ];
    }
}
