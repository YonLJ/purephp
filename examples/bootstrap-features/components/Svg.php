<?php declare(strict_types=1);

use Tiny\Core\Tag;

function Svg($icon)
{
    return(
        Tag::svg(
            Tag::use()->href("#$icon")
        )
    );
}
