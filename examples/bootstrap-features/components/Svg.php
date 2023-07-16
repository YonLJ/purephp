<?php

use Tiny\Tag;

function Svg($icon)
{
    return(
        Tag::svg(
            Tag::use()->href("#$icon")
        )
    );
}
