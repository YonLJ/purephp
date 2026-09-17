<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\CompileException;
use Pure\Compile\Internal\ArtifactCommand;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Compile\Internal\CodeGenerator;
use Pure\Compile\Internal\ShapeIndex;
use Pure\Compile\Renderer;
use Pure\Compile\Shape;

class ArtifactTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/purephp-artifact-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0o700, true);

        Compile::cachePath(null);
        Compile::flush();
    }

    protected function tearDown(): void
    {
        $this->remove($this->dir);

        Compile::flush();

        parent::tearDown();
    }

    public function testCompilesShapeFilesIntoStandaloneRenderers(): void
    {
        $file = $this->shapeFile('page.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Pages;

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\{div, h2};

            return Compile::shape(
                div(h2(Slot::text('title')))->class('card')
            );
            PHP);

        $artifact = ArtifactCompiler::write($file);

        $this->assertSame($this->dir . '/page.pure.php', $artifact);
        $this->assertFileExists($artifact);
        $this->assertStringContainsString('maps=0', (string)file_get_contents($artifact));

        $shape = self::load($file);
        $renderer = self::load($artifact);

        $this->assertInstanceOf(Shape::class, $shape);
        $this->assertInstanceOf(Renderer::class, $renderer);
        $this->assertSame($shape->id(), $renderer->id);
        $this->assertSame('', $renderer->source);
        $this->assertSame('<div class="card"><h2>a &amp; b</h2></div>', $renderer->render(['title' => 'a & b']));
        $this->assertSame($shape(['title' => 'a & b']), $renderer->render(['title' => 'a & b']));
    }

    public function testArtifactContentsAreDeterministic(): void
    {
        $file = $this->shapeFile(
            'stable.shape.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::text('v')));\n"
        );

        $first = ArtifactCompiler::build($file);

        $this->assertSame($first, ArtifactCompiler::build($file));
        $this->assertStringContainsString("declare(strict_types=1);\n", $first);
        $this->assertStringContainsString("Compiled from stable.shape.php", $first);
        $this->assertStringContainsString('// purephp-shape id=', $first);
        $this->assertStringContainsString('ob_start();', $first);
        $this->assertStringContainsString('<?= ', $first);
        $this->assertSame(1, substr_count($first, '$pureBody = static function'));
        $this->assertSame(0, substr_count($first, '$pureSource'));
        $this->assertSame(0, substr_count($first, '$out .= '));
    }

    public function testTemplateArtifactsRenderLikeTheFlatSource(): void
    {
        $file = $this->shapeFile('template.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Templates;

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\div;
            use function Pure\HTML\em;
            use function Pure\HTML\h1;
            use function Pure\HTML\li;
            use function Pure\HTML\p;
            use function Pure\HTML\span;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label'))->class(Slot::attr('class'))->id(Slot::attr('id')->default('n')));

            return Compile::shape(
                div(
                    h1(Slot::text('title')),
                    p(Slot::raw('body'))->title(Slot::attr('title')->default('t')),
                    Slot::child('meta', Compile::shape(span(Slot::text('label')))),
                    Slot::text('subtitle')->default('none'),
                    Slot::if('flag', Compile::shape(em('on')), Compile::shape(em('off'))),
                    Slot::if('absent', Compile::shape(em('never'))),
                    ul(Slot::each('items', $item, static fn (mixed $item): array => ['label' => '#', 'class' => 'x'])),
                    ul(Slot::eachKind('mixed', [
                        'a' => Compile::shape(li('A')),
                        'b' => Compile::shape(li('B')),
                    ]))
                )->class(Slot::attr('cardClass'))
            );
            PHP);

        $artifact = ArtifactCompiler::write($file);
        $contents = (string)file_get_contents($artifact);
        $shape = self::load($file);

        $this->assertInstanceOf(Shape::class, $shape);
        $this->assertStringContainsString('<?= TemplateRuntime::text(', $contents);
        $this->assertStringContainsString('<?= TemplateRuntime::raw(', $contents);
        $this->assertStringContainsString('TemplateRuntime::attr(', $contents);
        $this->assertStringContainsString('TemplateRuntime::attr($v, \'title\', default: \'t\')', $contents);
        $this->assertStringContainsString("path: 'items[].id', default: 'n'", $contents);
        $this->assertStringContainsString('endforeach;', $contents);
        $this->assertStringContainsString('endif;', $contents);
        $this->assertStringContainsString('else:', $contents);
        $this->assertStringContainsString('switch (', $contents);
        $this->assertStringContainsString('use Pure\Compile\Internal\TemplateRuntime;', $contents);
        $this->assertStringNotContainsString('array_key_exists', $contents);

        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(
            CodeGenerator::source($shape->tree(), $index),
            $index->id(),
            array_values($index->maps())
        );
        $template = self::load($artifact);

        $this->assertInstanceOf(Renderer::class, $template);

        $sets = [
            [
                'title' => 'T & <b>',
                'body' => '<b>raw</b>',
                'meta' => ['label' => 'M'],
                'flag' => true,
                'cardClass' => 'c1',
                'items' => [['label' => 'one', 'class' => 'i1'], ['label' => 'two']],
                'mixed' => [['kind' => 'a'], ['kind' => 'b']],
            ],
            [
                'title' => 'Empty',
                'body' => '',
                'meta' => ['label' => 'N'],
                'flag' => false,
                'cardClass' => null,
                'items' => [],
                'mixed' => [['kind' => 'b']],
            ],
        ];

        foreach ($sets as $set) {
            $this->assertSame($flat->render($set), $template->render($set));
        }
    }

    public function testInlinesMapClosuresWithTheirFileContext(): void
    {
        $file = $this->shapeFile('list.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Lists;

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\li;
            use function Pure\HTML\ul;
            use function Pure\Utils\clx;

            function label(mixed $item): array
            {
                return ['label' => '#' . $item];
            }

            $item = Compile::shape(li(Slot::text('label')));

            return Compile::shape(
                ul(
                    Slot::each('items', $item, static fn (mixed $item): array => label($item)),
                    Slot::each('classes', $item, static fn (mixed $item): array => ['label' => clx('x', (string)$item)])
                )
            );
            PHP);

        $artifact = ArtifactCompiler::write($file);
        $contents = (string)file_get_contents($artifact);

        $this->assertStringContainsString('maps=2', $contents);
        $this->assertStringContainsString('namespace App\Lists {', $contents);
        $this->assertStringContainsString('$pureMap0 = static fn (mixed $item): array => label($item);', $contents);
        $this->assertStringContainsString('use function Pure\Utils\clx;', $contents);
        $this->assertStringNotContainsString('use Pure\Compile\Compile;', $contents);
        $this->assertStringNotContainsString('use function Pure\HTML\li;', $contents);

        $renderer = self::load($artifact);

        $this->assertInstanceOf(Renderer::class, $renderer);
        $this->assertSame(
            '<ul><li>#a</li><li>#b</li><li>x a</li><li>x b</li></ul>',
            $renderer->render(['items' => ['a', 'b'], 'classes' => ['a', 'b']])
        );
    }

    public function testRefusesMapClosuresThatCaptureVariables(): void
    {
        $file = $this->shapeFile('capture.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label')));
            $prefix = '#';

            return Compile::shape(
                ul(Slot::each('items', $item, static fn (mixed $item): array => ['label' => $prefix . $item]))
            );
            PHP);

        try {
            ArtifactCompiler::write($file);
            $this->fail('the capture must be refused.');
        } catch (CompileException $error) {
            $this->assertStringContainsString("slot 'items[]'", $error->getMessage());
            $this->assertStringContainsString('captures $prefix', $error->getMessage());
        }

        $this->assertFileDoesNotExist($this->dir . '/capture.pure.php');
        $this->assertSame([], glob($this->dir . '/pure-artifact-*') ?: []);
    }

    public function testRefusesMapClosuresBoundToAnObject(): void
    {
        $file = $this->shapeFile('bound.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label')));
            $mapper = new class {
                public function map(mixed $item): array
                {
                    return ['label' => (string)$item];
                }
            };

            return Compile::shape(
                ul(Slot::each('items', $item, $mapper->map(...)))
            );
            PHP);

        try {
            ArtifactCompiler::write($file);
            $this->fail('the bound closure must be refused.');
        } catch (CompileException $error) {
            $this->assertStringContainsString('is bound to an object', $error->getMessage());
        }
    }

    public function testRefusesMapClosuresThatUseTheirDefiningFile(): void
    {
        $file = $this->shapeFile('context.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label')));

            return Compile::shape(
                ul(Slot::each('items', $item, static fn (mixed $item): array => ['label' => __DIR__ . $item]))
            );
            PHP);

        try {
            ArtifactCompiler::write($file);
            $this->fail('the context dependent closure must be refused.');
        } catch (CompileException $error) {
            $this->assertStringContainsString('uses __DIR__', $error->getMessage());
        }
    }

    public function testRefusesClosuresThatShareALineWithAnother(): void
    {
        $file = $this->shapeFile('crowded.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label')));

            return Compile::shape(ul(Slot::each('items', $item, static fn (mixed $item): array => ['a' => $item]), Slot::each('other', $item, static fn (mixed $item): array => ['b' => $item])));
            PHP);

        try {
            ArtifactCompiler::write($file);
            $this->fail('the ambiguous closure must be refused.');
        } catch (CompileException $error) {
            $this->assertStringContainsString('shares a line with another closure', $error->getMessage());
        }
    }

    public function testReferencesNamedMapCallablesByCallable(): void
    {
        $file = $this->shapeFile('named.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label')));

            return Compile::shape(
                ul(Slot::each('items', $item, \Closure::fromCallable('artifactMapLabel')))
            );
            PHP);

        $artifact = ArtifactCompiler::write($file);
        $contents = (string)file_get_contents($artifact);

        $this->assertStringContainsString("\\Closure::fromCallable('artifactMapLabel')", $contents);
        $this->assertStringContainsString("namespace {\n", $contents);

        $renderer = self::load($artifact);

        $this->assertInstanceOf(Renderer::class, $renderer);
        $this->assertSame('<ul><li>#a</li></ul>', $renderer->render(['items' => ['a']]));
    }

    public function testPlainViewsAreDependencyFreeAndRenderIdentically(): void
    {
        $file = $this->shapeFile('plain.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\div;
            use function Pure\HTML\h1;
            use function Pure\HTML\li;
            use function Pure\HTML\p;
            use function Pure\HTML\span;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label'))->class(Slot::attr('class')));

            return Compile::shape(
                div(
                    h1(Slot::text('title')),
                    p(Slot::raw('body')),
                    Slot::text('subtitle')->default('none'),
                    Slot::if('flag', Compile::shape(span('on')), Compile::shape(span('off'))),
                    ul(Slot::each('items', $item))
                )->class(Slot::attr('cardClass'))->title(Slot::attr('tip')->default('t'))
            );
            PHP);

        $plain = ArtifactCompiler::writeAll($file, true)['plain'];
        $this->assertIsString($plain);

        $source = (string)file_get_contents($plain);

        $this->assertStringNotContainsString('use ', $source);
        $this->assertStringNotContainsString('Pure', $source);
        $this->assertStringNotContainsString('Renderer', $source);
        $this->assertStringContainsString('declare(strict_types=1);', $source);
        $this->assertStringContainsString('htmlspecialchars((string)$title', $source);
        $this->assertStringContainsString('htmlspecialchars((string)$item1[\'label\']', $source);
        $this->assertStringContainsString('foreach ($items as $item1):', $source);
        $this->assertStringContainsString('if ((bool)($flag ?? false)):', $source);
        $this->assertStringContainsString('class="<?= htmlspecialchars', $source);

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(
            CodeGenerator::source($shape->tree(), $index),
            $index->id(),
            array_values($index->maps())
        );

        $sets = [
            [
                'title' => 'T & <b>',
                'body' => '<b>raw</b>',
                'flag' => true,
                'cardClass' => 'c1',
                'items' => [['label' => 'one', 'class' => 'i1'], ['label' => 'two', 'class' => 'i2']],
                'tip' => 'tip',
            ],
            [
                'title' => 'Empty',
                'body' => '',
                'subtitle' => 'S',
                'flag' => false,
                'cardClass' => 'c2',
                'items' => [],
                'tip' => 'x',
            ],
        ];

        $header = $shape->tree()->documentHeader();

        foreach ($sets as $set) {
            $this->assertSame($header . $flat->render($set), self::renderPlain($plain, $set));
        }
    }

    public function testPlainViewsWrapNamespacedMapsInANamespaceBlock(): void
    {
        $file = $this->shapeFile('plain-named.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Lists;

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('label')));

            return Compile::shape(
                ul(Slot::each('items', $item, static fn (mixed $item): array => ['label' => li($item)]))
            );
            PHP);

        $sources = ArtifactCompiler::writeAll($file, true);
        $plain = (string)$sources['plain'];
        $source = (string)file_get_contents($plain);

        $this->assertStringContainsString('namespace App\\Lists {', $source);
        $this->assertStringContainsString('use function Pure\\HTML\\li;', $source);
        $this->assertStringContainsString("namespace {\n", $source);
        $this->assertStringNotContainsString('Renderer', $source);

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(
            CodeGenerator::source($shape->tree(), $index),
            $index->id(),
            array_values($index->maps())
        );

        $data = ['items' => ['a', 'b']];

        $this->assertSame($shape->tree()->documentHeader() . $flat->render($data), self::renderPlain($plain, $data));
    }

    public function testCompilePlainWritesAndChecksBothFlavours(): void
    {
        $file = $this->shapeFile(
            'flavours.shape.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::text('v')));\n"
        );
        $command = new ArtifactCommand();

        $compiled = $this->runCommand($command, ['pure', 'compile', '--plain', $file]);
        $this->assertSame(0, $compiled['code']);
        $this->assertFileExists($this->dir . '/flavours.pure.php');
        $this->assertFileExists($this->dir . '/flavours.plain.php');
        $this->assertStringContainsString('flavours.pure.php, ' . $this->dir . '/flavours.plain.php', $compiled['stdout']);

        $fresh = $this->runCommand($command, ['pure', 'compile', '--check', '--plain', $file]);
        $this->assertSame(0, $fresh['code']);
        $this->assertSame(2, substr_count($fresh['stdout'], 'up to date:'));

        $reordered = $this->runCommand($command, ['pure', 'compile', '--plain', '--check', $file]);
        $this->assertSame(0, $reordered['code']);

        unlink($this->dir . '/flavours.plain.php');

        $missing = $this->runCommand($command, ['pure', 'compile', '--check', '--plain', $file]);
        $this->assertSame(1, $missing['code']);
        $this->assertStringContainsString('missing: ' . $this->dir . '/flavours.plain.php', $missing['stdout']);

        file_put_contents($this->dir . '/flavours.plain.php', "<?php\n// tampered\n");

        $stale = $this->runCommand($command, ['pure', 'compile', '--check', '--plain', $file]);
        $this->assertSame(1, $stale['code']);
        $this->assertStringContainsString('stale: ' . $this->dir . '/flavours.plain.php', $stale['stdout']);
    }

    public function testPlainViewsHandleChildAndEachKindSlots(): void
    {
        $file = $this->shapeFile('plain-scopes.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\div;
            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            $item = Compile::shape(li(Slot::text('value')));
            $list = Compile::shape(ul(Slot::each('items', $item)));

            return Compile::shape(
                div(
                    Slot::child('meta', $list),
                    ul(
                        Slot::eachKind('blocks', [
                            'text' => Compile::shape(li(Slot::text('value'))),
                            'link' => Compile::shape(li(Slot::text('value'))->class('link')),
                        ])
                    )
                )
            );
            PHP);

        $plain = (string)ArtifactCompiler::build($file, true);
        $written = ArtifactCompiler::writeAll($file, true);
        $this->assertStringContainsString('foreach ($meta[\'items\'] as $item', $plain);
        $this->assertStringContainsString('switch ($item3[\'kind\'] ?? null)', $plain);
        $this->assertStringContainsString("case 'link':", $plain);

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(
            CodeGenerator::source($shape->tree(), $index),
            $index->id(),
            array_values($index->maps())
        );

        $data = [
            'meta' => ['items' => [['value' => 'm1'], ['value' => 'm2']]],
            'blocks' => [['kind' => 'text', 'value' => 'a'], ['kind' => 'link', 'value' => 'b']],
        ];

        $this->assertSame(
            $shape->tree()->documentHeader() . $flat->render($data),
            self::renderPlain((string)$written['plain'], $data)
        );
    }

    public function testPlainViewsFallBackToDataOffsetsForOddSlotNames(): void
    {
        $file = $this->shapeFile('plain-names.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\div;

            return Compile::shape(
                div(
                    Slot::text('user-name'),
                    Slot::text('data')->default('fallback'),
                    Slot::text('v1')->default('numbered')
                )
            );
            PHP);

        $plain = (string)ArtifactCompiler::build($file, true);

        $this->assertStringContainsString('$data[\'user-name\']', $plain);
        $this->assertStringContainsString('($data[\'data\'] ?? \'fallback\')', $plain);
        $this->assertStringContainsString('($data[\'v1\'] ?? \'numbered\')', $plain);

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(
            CodeGenerator::source($shape->tree(), $index),
            $index->id(),
            array_values($index->maps())
        );

        $data = ['user-name' => 'Ann', 'data' => 'D', 'v1' => 'V'];

        $written = ArtifactCompiler::writeAll($file, true);

        $this->assertSame(
            $shape->tree()->documentHeader() . $flat->render($data),
            self::renderPlain((string)$written['plain'], $data)
        );
    }

    public function testPlainViewsOfStaticShapesKeepASinglePhpBlock(): void
    {
        $file = $this->shapeFile(
            'plain-static.shape.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('static & <raw>'));\n"
        );

        $plain = (string)ArtifactCompiler::build($file, true);

        $this->assertSame(1, substr_count($plain, '<?php'));
        $this->assertStringNotContainsString('foreach', $plain);

        $written = ArtifactCompiler::writeAll($file, true);

        $this->assertSame(
            '<!DOCTYPE html><div>static &amp; &lt;raw&gt;</div>',
            self::renderPlain((string)$written['plain'], [])
        );
    }

    public function testCompileRequiresAtLeastOnePath(): void
    {
        $command = new ArtifactCommand();

        $missing = $this->runCommand($command, ['pure', 'compile']);
        $this->assertSame(1, $missing['code']);
        $this->assertStringContainsString('needs at least one file or directory', $missing['stderr']);

        $checked = $this->runCommand($command, ['pure', 'compile', '--plain', '--check']);
        $this->assertSame(1, $checked['code']);
        $this->assertStringContainsString('Usage:', $checked['stderr']);
    }

    public function testPlainPathsFollowTheShapeSuffix(): void
    {
        $this->assertSame($this->dir . '/page.pure.php', ArtifactCompiler::artifactPath($this->dir . '/page.shape.php'));
        $this->assertSame($this->dir . '/page.plain.php', ArtifactCompiler::plainPath($this->dir . '/page.shape.php'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a *.shape.php file');

        ArtifactCompiler::plainPath($this->dir . '/page.php');
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function renderPlain(string $file, array $data): string
    {
        ob_start();
        extract($data, EXTR_SKIP);
        require $file;

        return (string)ob_get_clean();
    }

    public function testCheckModeReportsMissingAndStaleArtifacts(): void
    {
        $file = $this->shapeFile(
            'check.shape.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::text('v')));\n"
        );
        $command = new ArtifactCommand();

        $missing = $this->runCommand($command, ['pure', 'compile', '--check', $file]);
        $this->assertSame(1, $missing['code']);
        $this->assertStringContainsString('missing:', $missing['stdout']);

        $compiled = $this->runCommand($command, ['pure', 'compile', $file]);
        $this->assertSame(0, $compiled['code']);
        $this->assertStringContainsString('compiled:', $compiled['stdout']);

        $fresh = $this->runCommand($command, ['pure', 'compile', '--check', $file]);
        $this->assertSame(0, $fresh['code']);
        $this->assertStringContainsString('up to date:', $fresh['stdout']);

        file_put_contents($this->dir . '/check.pure.php', "<?php\n// stale\n");

        $stale = $this->runCommand($command, ['pure', 'compile', '--check', $file]);
        $this->assertSame(1, $stale['code']);
        $this->assertStringContainsString('stale:', $stale['stdout']);
        $this->assertStringContainsString('need recompiling', $stale['stderr']);
    }

    public function testCompilesDirectoriesRecursively(): void
    {
        mkdir($this->dir . '/nested');
        $this->shapeFile('a.shape.php', "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('a'));\n");
        $this->shapeFile('nested/b.shape.php', "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('b'));\n");
        file_put_contents($this->dir . '/nested/notes.txt', 'ignored');

        $result = $this->runCommand(new ArtifactCommand(), ['pure', 'compile', $this->dir]);

        $this->assertSame(0, $result['code']);
        $this->assertFileExists($this->dir . '/a.pure.php');
        $this->assertFileExists($this->dir . '/nested/b.pure.php');
        $this->assertStringContainsString('a.shape.php', $result['stdout']);
        $this->assertStringContainsString('b.shape.php', $result['stdout']);
    }

    public function testRejectsFilesThatAreNotShapes(): void
    {
        $command = new ArtifactCommand();

        $broken = $this->runCommand($command, ['pure', 'compile', $this->shapeFile('broken.shape.php', "<?php\n\nreturn 42;\n")]);
        $this->assertSame(1, $broken['code']);
        $this->assertStringContainsString('must return a Pure\\Compile\\Shape', $broken['stderr']);

        $suffix = $this->runCommand($command, ['pure', 'compile', $this->shapeFile('page.php', "<?php\n\nreturn null;\n")]);
        $this->assertSame(1, $suffix['code']);
        $this->assertStringContainsString('is not a *.shape.php file', $suffix['stderr']);

        $missing = $this->runCommand($command, ['pure', 'compile', $this->dir . '/absent.shape.php']);
        $this->assertSame(1, $missing['code']);
        $this->assertStringContainsString('does not exist', $missing['stderr']);

        $usage = $this->runCommand($command, ['pure', 'compile', '--nope']);
        $this->assertSame(1, $usage['code']);
        $this->assertStringContainsString("unknown option '--nope'", $usage['stderr']);
    }

    public function testArtifactsCaptureTheDocumentHeader(): void
    {
        $file = $this->shapeFile('document.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\{body, head, html};

            return Compile::shape(
                html(head(), body(Slot::text('content')))
            );
            PHP);

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);

        $renderer = self::load(ArtifactCompiler::write($file));

        $this->assertInstanceOf(Renderer::class, $renderer);
        $this->assertNotFalse($renderer->save($this->dir . '/artifact.html', ['content' => 'hi']));
        $shape->save($this->dir . '/shape.html', ['content' => 'hi']);

        $rendered = (string)file_get_contents($this->dir . '/artifact.html');

        $this->assertSame('<!DOCTYPE html><html><head></head><body>hi</body></html>', $rendered);
        $this->assertSame((string)file_get_contents($this->dir . '/shape.html'), $rendered);
    }

    public function testDiscardsOutputEmittedWhileTheShapeFileLoads(): void
    {
        $file = $this->shapeFile(
            'noisy.shape.php',
            "<?php\n\ndeclare(strict_types=1);\n\necho 'sprite';\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('noisy'));\n"
        );

        ob_start();
        $artifact = ArtifactCompiler::write($file);
        $output = (string)ob_get_clean();

        $renderer = self::load($artifact);

        $this->assertSame('', $output);
        $this->assertInstanceOf(Renderer::class, $renderer);
        $this->assertSame('<div>noisy</div>', $renderer->render([]));
    }

    public function testCliBinaryCompilesAndChecks(): void
    {
        $file = $this->shapeFile('cli.shape.php', "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('cli'));\n");
        $binary = dirname(__DIR__) . '/bin/pure';

        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($binary) . ' --help 2>&1', $help, $helpCode);
        $this->assertSame(0, $helpCode);
        $this->assertStringContainsString('Usage:', implode("\n", $help));

        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($binary) . ' compile ' . escapeshellarg($file) . ' 2>&1', $compile, $compileCode);
        $this->assertSame(0, $compileCode);
        $this->assertFileExists($this->dir . '/cli.pure.php');
        $this->assertStringContainsString('compiled:', implode("\n", $compile));

        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($binary) . ' compile --check ' . escapeshellarg($file) . ' 2>&1', $check, $checkCode);
        $this->assertSame(0, $checkCode);
        $this->assertStringContainsString('up to date:', implode("\n", $check));
    }

    /**
     * @param list<string> $argv
     * @return array{code: int, stdout: string, stderr: string}
     */
    private function runCommand(ArtifactCommand $command, array $argv): array
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');

        if ($stdout === false || $stderr === false) {
            $this->fail('could not open the memory streams.');
        }

        $code = $command->run($argv, $stdout, $stderr);

        rewind($stdout);
        rewind($stderr);
        $output = (string)stream_get_contents($stdout);
        $errors = (string)stream_get_contents($stderr);
        fclose($stdout);
        fclose($stderr);

        return ['code' => $code, 'stdout' => $output, 'stderr' => $errors];
    }

    private function shapeFile(string $name, string $code): string
    {
        $path = $this->dir . '/' . $name;
        file_put_contents($path, $code);

        return $path;
    }

    private static function load(string $file): mixed
    {
        return (static fn (string $path): mixed => require $path)($file);
    }

    private function remove(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo) {
                $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
            }
        }

        @rmdir($dir);
    }
}

/**
 * @return array<string, string>
 */
function artifactMapLabel(mixed $item): array
{
    return ['label' => '#' . (string)$item];
}
