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

    public function __toString()
    {
        return $this->content;
    }

    public function toJSON()
    {
        $type = $this->type === RawType::HTML ? 'HTML' : 'XML';
        return [
            'type' => $type,
            'content' => $this->content
        ];
    }
}

function rawHtml(string $content)
{
    return new Raw(RawType::HTML, $content);
}

function rawXml(string $content)
{
    return new Raw(RawType::XML, $content);
}
