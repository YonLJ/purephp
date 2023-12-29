<?php declare(strict_types=1);
require '../../vendor/autoload.php';

use function Tiny\Tags\HTML\button;
use function Tiny\Tags\HTML\div;
use function Tiny\Tags\HTML\h1;
use function Tiny\Tags\HTML\span;

echo (
    div(
        h1('JavaScript Counter App'),
        div(
            button('+')->id('add')->onclick('handleAdd()'),
            span(0)->id('output'),
            button('-')->id('subtract'),
        )->class('counter-container')
    )
);
