<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h5, nav};

require_once __DIR__ . '/NavLink.cmp.php';

/**
 * The page header template: the company name, the nav links and the sign-up
 * link. The links are rendered by NavLink() and injected as raw markup.
 */
register('PageHeader', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h5(Slot::text('company'))->class('my-0 mr-md-auto font-weight-normal'),
        nav(Slot::raw('navs'))->class('my-2 my-md-0 mr-md-3'),
        Slot::raw('signUp')
    )->class('d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow')
));

/**
 * The page header: the company name, the nav links and the sign-up link.
 *
 * @param list<array{text: string, href: string, class: string}> $navs
 * @param array{text: string, href: string, class: string} $signUp
 */
function PageHeader(string $companyName, array $navs, array $signUp): Raw
{
    $links = [];

    foreach ($navs as $nav) {
        $links[] = NavLink($nav['text'], $nav['href'], $nav['class']);
    }

    return render(
        'PageHeader',
        company: $companyName,
        navs: $links,
        signUp: NavLink($signUp['text'], $signUp['href'], $signUp['class']),
    );
}
