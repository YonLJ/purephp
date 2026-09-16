<?php

declare(strict_types=1);

namespace Pure\Utils;

use Pure\Core\Raw;
use Pure\Core\RawType;

function rawHtml(string $content): Raw
{
    return new Raw(RawType::HTML, $content);
}

function rawXml(string $content): Raw
{
    return new Raw(RawType::XML, $content);
}
