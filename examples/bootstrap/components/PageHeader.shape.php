<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h5;
use function Pure\HTML\nav;

/**
 * The page header template: the company name, the nav links and the sign-up
 * link. The links are rendered by NavLink() and injected as raw markup.
 */
return Compile::shape(
    div(
        h5(Slot::text('company'))->class('my-0 mr-md-auto font-weight-normal'),
        nav(Slot::raw('navs'))->class('my-2 my-md-0 mr-md-3'),
        Slot::raw('signUp')
    )->class('d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow')
);
