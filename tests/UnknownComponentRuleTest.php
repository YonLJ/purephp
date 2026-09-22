<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * End-to-end test of the component-name rule: run phpstan over a fixture
 * project that enables only the collectors and UnknownComponentRule, and
 * read the JSON report.
 */
final class UnknownComponentRuleTest extends TestCase
{
    public function testReportsLiteralComponentNamesNoRegistrationDeclares(): void
    {
        $dir = sys_get_temp_dir() . '/purephp-rule-' . uniqid();
        mkdir($dir . '/src', 0o777, true);
        mkdir($dir . '/cache', 0o777, true);

        try {
            file_put_contents($dir . '/src/known.cmp.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Pure\Compile\Compile;
                use Pure\Component\Call;
                use Pure\Core\Slot;

                use function Pure\Component\{component, register};
                use function Pure\HTML\span;

                function KnownBadge(mixed ...$children): Call
                {
                    return component(__FUNCTION__, ...$children);
                }

                register(KnownBadge(...), static fn (): \Pure\Compile\Shape => Compile::shape(
                    span(Slot::value('label'))
                ));
                PHP);

            file_put_contents($dir . '/src/static.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Pure\Compile\Compile;
                use Pure\Component\Registry;
                use Pure\Core\Slot;

                use function Pure\HTML\span;

                Registry::register('StaticBadge', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                    span(Slot::value('label'))
                ));
                PHP);

            file_put_contents($dir . '/src/fcp.cmp.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Pure\Compile\Compile;
                use Pure\Component\Call;
                use Pure\Core\Slot;

                use function Pure\Component\{component, register};
                use function Pure\HTML\span;

                function FcBox(mixed ...$children): Call
                {
                    return component(__FUNCTION__, ...$children);
                }

                register(FcBox(...), static fn (): \Pure\Compile\Shape => Compile::shape(
                    span(Slot::value('label'))
                ));
                PHP);

            file_put_contents($dir . '/src/calls.php', <<<'PHP'
                <?php

                declare(strict_types=1);

                use Pure\Component\Registry;

                use function Pure\Component\component;

                component('KnownBadge');
                component('KnownBadg');
                component('FcBox');
                Registry::component('StaticBadge');
                Registry::component('StaticBadg');
                PHP);

            $config = $dir . '/phpstan.neon';
            file_put_contents($config, <<<NEON
                services:
                    -
                        class: Pure\StaticAnalysis\ComponentCallCollector
                        tags:
                            - phpstan.collector
                    -
                        class: Pure\StaticAnalysis\RegistryCallCollector
                        tags:
                            - phpstan.collector

                rules:
                    - Pure\StaticAnalysis\UnknownComponentRule

                parameters:
                    customRulesetUsed: true
                    tmpDir: {$dir}/cache
                NEON);

            $command = implode(' ', [
                escapeshellarg(PHP_BINARY),
                escapeshellarg(__DIR__ . '/../vendor/bin/phpstan'),
                'analyse',
                '--no-progress',
                '--error-format=json',
                '--configuration=' . escapeshellarg($config),
                escapeshellarg($dir . '/src'),
            ]);

            exec($command . ' 2>&1', $output, $code);

            // phpstan prints an error-identifier explainer before the JSON
            // document; the report itself starts at the first brace.
            $raw = implode("\n", $output);
            $brace = strpos($raw, '{');
            $report = json_decode($brace === false ? '' : substr($raw, $brace), true);
            $this->assertIsArray($report, 'phpstan must print a JSON report: ' . $raw);

            $messages = [];

            foreach ($report['files'] ?? [] as $file) {
                foreach ($file['messages'] ?? [] as $message) {
                    $messages[] = $message['message'];
                }
            }

            $this->assertSame(1, $code, 'the typo must fail the run');

            foreach (['KnownBadg', 'StaticBadg'] as $typo) {
                $matched = array_filter(
                    $messages,
                    static fn (string $message): bool => str_contains($message, "'{$typo}' is not registered")
                );

                $this->assertNotSame([], array_values($matched), "the typo '{$typo}' must be reported: " . implode(' | ', $messages));
            }

            foreach (['KnownBadge', 'StaticBadge', 'FcBox'] as $known) {
                foreach ($messages as $message) {
                    $this->assertStringNotContainsString("'{$known}' is not registered", $message);
                }
            }
        } finally {
            self::remove($dir);
        }
    }

    private static function remove(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;

            if (is_dir($path)) {
                self::remove($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($dir);
    }
}
