<?php declare(strict_types=1);

require_once __DIR__ . '/NavLink.php';

use Pure\Core\Raw;

use function Pure\Component\render;

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
        $links[] = (string)NavLink($nav['text'], $nav['href'], $nav['class']);
    }

    return render(
        __DIR__ . '/PageHeader.shape.php',
        company: $companyName,
        navs: implode('', $links),
        signUp: (string)NavLink($signUp['text'], $signUp['href'], $signUp['class']),
    );
}
