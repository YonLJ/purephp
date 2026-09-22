<?php

declare(strict_types=1);

use Pure\Component\Registry;

/**
 * Test support: writes a unit file in the recommended form — a call function
 * next to register(Name(...)) — and loads it. The call function is declared
 * conditionally and the file is include()d (not require_once), so a registry
 * reset between two passes over one test class can load the file again:
 * the function declaration is skipped and register() runs once more.
 */
final class ModernUnit
{
    /**
     * @param string $prepareSource The literal prepare() hook of the unit, or '' for none.
     */
    public static function load(string $file, string $name, string $prepareSource = ''): string
    {
        $prepare = $prepareSource === '' ? '' : ",\n    prepare: {$prepareSource}";
        $source = <<<PHP
            <?php

            declare(strict_types=1);

            use Pure\Component\Call;
            use Pure\Component\Prop;
            use Pure\Component\Trusted;

            use function Pure\Component\{component, register};

            if (!function_exists('{$name}')) {
                function {$name}(mixed ...\$children): Call
                {
                    return component(__FUNCTION__, ...\$children);
                }
            }

            register({$name}(...),
                factory: static fn (): mixed => \UnitFactory::run('{$name}'){$prepare}
            );
            PHP;

        // Rewrite when the content differs: a file left behind by an aborted
        // run (or written by an older generator) must not be loaded as-is.
        if (!is_file($file) || file_get_contents($file) !== $source) {
            file_put_contents($file, $source);
        }

        if (!in_array($name, Registry::names(), true)) {
            include $file;
        }

        return $file;
    }
}
