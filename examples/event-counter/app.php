<?php declare(strict_types=1);
require '../../vendor/autoload.php';

use Pure\Compile\Compile;

use function Pure\HTML\button;
use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\span;

$page = Compile::shape(
    div(
        h1('JavaScript Counter App'),
        div(
            button('+')->id('add')->onclick('handleAdd()'),
            span(0)->id('output'),
            button('-')->id('subtract'),
        )->class('counter-container')
    )
);

$page->print([]);
