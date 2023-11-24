<?php declare(strict_types=1);
namespace Tiny\Utils;

function sty(array $list)
{
    $styleList = [];
    foreach ($list as $key => $val) {
        $styleList[] = "$key: $val";
    }
    return join(';', $styleList) . ';';
}
