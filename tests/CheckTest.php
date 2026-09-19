<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\CheckCommand;
use Pure\Component\Registry;
use Pure\Core\DevMode;

class CheckTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/purephp-check-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0o700, true);

        Compile::cachePath(null);
        Compile::flush();
        Registry::reset();
        DevMode::reset();
    }

    protected function tearDown(): void
    {
        Registry::reset();
        $this->remove($this->dir);
        Compile::flush();

        parent::tearDown();
    }

    public function testCleanUnitPasses(): void
    {
        $file = $this->unitFile('clean.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\{div, li, ul};

            $item = Compile::shape(li(Slot::value('label')));

            register('CleanBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'), ul(Slot::each('items', $item)))
            ));

            function CleanBox(string $title, array $items): string
            {
                return render('CleanBox', title: $title, items: $items);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString("ok: component 'CleanBox' -> {$file}", $result['stdout']);
        $this->assertStringContainsString('checked 1 unit(s): 0 error(s), 0 warning(s).', $result['stdout']);
    }

    public function testBindingTypoIsAnErrorWithASuggestion(): void
    {
        $file = $this->unitFile('typo.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('TypoCard', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function TypoCard(string $title): string
            {
                return render('TypoCard', titel: $title);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "error: component 'TypoCard' -> {$file}: render() binds 'titel' but the template does not read it (did you mean 'title'?)",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "error: component 'TypoCard' -> {$file}: required slot 'title' is not bound by render()",
            $result['stdout']
        );
    }

    public function testTypeMismatchBetweenSlotAndParameterIsAnError(): void
    {
        $file = $this->unitFile('types.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\{div, li, ul};

            $item = Compile::shape(li(Slot::value('label')));

            register('TypeBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'), ul(Slot::each('items', $item)))
            ));

            function TypeBox(string $title, string $items): string
            {
                return render('TypeBox', title: $title, items: $items);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "slot 'items' is a list slot but parameter \$items is typed string",
            $result['stdout']
        );
        $this->assertStringNotContainsString("slot 'title'", $result['stdout']);
    }

    public function testNullableParameterForARequiredSlotWarns(): void
    {
        $file = $this->unitFile('nullable.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('NullBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function NullBox(?string $title): string
            {
                return render('NullBox', title: $title);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString(
            'warning: component \'NullBox\': parameter $title is nullable but slot \'title\' is required; binding null throws MissingSlotException',
            str_replace(" -> {$file}", '', $result['stdout'])
        );

        $strict = $this->runCheck(['pure', 'check', '--strict', $file]);

        $this->assertSame(1, $strict['code']);
    }

    public function testUnusedParameterWarns(): void
    {
        $file = $this->unitFile('unused.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('UnusedBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function UnusedBox(string $title, string $extra): string
            {
                return render('UnusedBox', title: $title);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString(
            'parameter $extra is neither used by the function nor a slot of the template',
            $result['stdout']
        );
    }

    public function testPageUnitWithoutAComponentFunctionIsCheckedThroughItsBindingsHelper(): void
    {
        $file = $this->unitFile('page.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('PageBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function pageBoxBindings(): array
            {
                return ['title' => 'hello'];
            }

            function pageBoxPage(): string
            {
                return render('PageBox', ...pageBoxBindings());
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString(
            "no function named 'PageBox' is defined in this file; parameter types are not checked",
            $result['stdout']
        );
        $this->assertStringNotContainsString('slots are not compared', $result['stdout']);
        $this->assertStringContainsString('0 error(s), 0 warning(s).', $result['stdout']);
    }

    public function testBindingsHelperTypoIsAnError(): void
    {
        $file = $this->unitFile('page-typo.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('TypoPageBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function typoPageBindings(): array
            {
                return ['titel' => 'hello'];
            }

            function typoPagePage(): string
            {
                return render('TypoPageBox', ...typoPageBindings());
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "render() binds 'titel' but the template does not read it (did you mean 'title'?)",
            $result['stdout']
        );
    }

    public function testDynamicBindingsAreNotCompared(): void
    {
        $file = $this->unitFile('dynamic.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('DynamicBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function DynamicBox(array $data): string
            {
                return render('DynamicBox', ...$data);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('slots are not compared: render() unpacks its bindings', $result['stdout']);
    }

    public function testPositionalDataIsAnError(): void
    {
        $file = $this->unitFile('positional.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('PositionalBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function PositionalBox(string $title): string
            {
                return render('PositionalBox', $title);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            'render() passes data by position; slot values must be named',
            $result['stdout']
        );
    }

    public function testShapeFileWithConflictingSlotKindsIsAnError(): void
    {
        $file = $this->shapeFile('conflict.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\{div, li, ul};

            $item = Compile::shape(li(Slot::value('label')));

            return Compile::shape(
                div(Slot::value('items'), ul(Slot::each('items', $item)))
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "slot 'items' is used as a value or raw slot and as a child or list scope; one data key cannot be both",
            $result['stdout']
        );
    }

    public function testCleanShapeFilePasses(): void
    {
        $file = $this->shapeFile('clean.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\div;

            return Compile::shape(div(Slot::value('title')));
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString("ok: {$file} (shape)", $result['stdout']);
    }

    public function testDirectoryIsSearchedRecursively(): void
    {
        mkdir($this->dir . '/views', 0o700);
        $this->shapeFile('views/one.shape.php', "<?php\n\ndeclare(strict_types=1);\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('x'));\n");

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('checked 1 unit(s)', $result['stdout']);
    }

    public function testRenderByFilePathAndNamespacedFunctionsAreResolved(): void
    {
        $file = $this->unitFile('namespaced.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Blocks;

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('NamespacedBox', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function NamespacedBox(string $title): string
            {
                return render(__FILE__, title: $title);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('0 error(s), 0 warning(s).', $result['stdout']);
        $this->assertStringNotContainsString('slots are not compared', $result['stdout']);
    }

    public function testUsageErrors(): void
    {
        $missing = $this->runCheck(['pure', 'check']);
        $this->assertSame(1, $missing['code']);
        $this->assertStringContainsString('check needs at least one file or directory', $missing['stderr']);

        $unknown = $this->runCheck(['pure', 'check', '--nope', $this->dir]);
        $this->assertSame(1, $unknown['code']);
        $this->assertStringContainsString("unknown option '--nope'", $unknown['stderr']);

        $help = $this->runCheck(['pure', 'check', '--help']);
        $this->assertSame(0, $help['code']);
        $this->assertStringContainsString('Usage:', $help['stdout']);
        $this->assertStringContainsString('pure compile --check', $help['stdout']);
    }

    public function testMissingPathIsReported(): void
    {
        $result = $this->runCheck(['pure', 'check', $this->dir . '/missing']);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString('does not exist', $result['stderr']);
    }

    private function unitFile(string $name, string $code): string
    {
        $path = $this->dir . '/' . $name;
        file_put_contents($path, $code);

        return $path;
    }

    private function shapeFile(string $name, string $code): string
    {
        $path = $this->dir . '/' . $name;
        file_put_contents($path, $code);

        return $path;
    }

    /**
     * @param list<string> $argv
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function runCheck(array $argv): array
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');

        $this->assertIsResource($stdout);
        $this->assertIsResource($stderr);

        $command = new CheckCommand(static fn (string $file): array => Registry::unitsFor($file));
        $code = $command->run($argv, $stdout, $stderr);

        rewind($stdout);
        rewind($stderr);

        return [
            'code' => $code,
            'stdout' => (string)stream_get_contents($stdout),
            'stderr' => (string)stream_get_contents($stderr),
        ];
    }

    private function remove(string $dir): void
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
                $this->remove($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($dir);
    }
}
