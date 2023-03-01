<?php

use Tiny\Tag;

function Svg($icon)
{
    extract($props);
    return(
        Tag::svg(
            Tag::use()->href("#$icon")
        )
    );
}
