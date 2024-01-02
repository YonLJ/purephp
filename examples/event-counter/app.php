<?php declare(strict_types=1);
require '../../vendor/autoload.php';

use function Pure\Tags\HTML\button;
use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\h1;
use function Pure\Tags\HTML\span;

div(
    h1('JavaScript Counter App'),
    div(
        button('+')->id('add')->onclick('handleAdd()'),
        span(0)->id('output'),
        button('-')->id('subtract'),
    )->class('counter-container')
)->toPrint();
