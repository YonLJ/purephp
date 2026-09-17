<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\footer;

/**
 * The page footer template: the logo column and the link columns, rendered by
 * ColLogo() and ColLinks() and injected as raw markup.
 */
return Compile::shape(
    footer(
        div(
            Slot::raw('logo'),
            Slot::raw('columns')
        )->class('row'),
    )->class('pt-4 my-md-5 pt-md-5 border-top')
);
