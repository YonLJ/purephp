<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ArtifactCommand;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Compile\Internal\CodeGenerator;
use Pure\Compile\Internal\ShapeIndex;
use Pure\Compile\Renderer;
use Pure\Compile\Shape;
use Pure\Component\Registry;
use Pure\Core\Raw;

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
        Registry::reset();
    }

    protected function tearDown(): void
    {
        Registry::reset();
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
                    Slot::child('meta', span(Slot::text('label'))),
                    Slot::text('subtitle')->default('none'),
                    Slot::if('flag', em('on'), em('off')),
                    Slot::if('absent', em('never')),
                    ul(Slot::each('items', $item)),
                    ul(Slot::eachKind('mixed', [
                        'a' => li('A'),
                        'b' => li('B'),
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
        $flat = CodeGenerator::fromSource(CodeGenerator::source($shape->tree()), $index->id());
        $template = self::load($artifact);

        $this->assertInstanceOf(Renderer::class, $template);

        $sets = [
            [
                'title' => 'T & <b>',
                'body' => '<b>raw</b>',
                'meta' => ['label' => 'M'],
                'flag' => true,
                'cardClass' => 'c1',
                'items' => [['label' => 'one', 'class' => 'i1'], ['label' => 'two', 'class' => 'i2']],
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

    public function testArtifactSlotsFallBackToTheSlowPathsLikeTheFlatRenderer(): void
    {
        $file = $this->shapeFile('cold.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\div;
            use function Pure\HTML\em;
            use function Pure\HTML\li;
            use function Pure\HTML\p;
            use function Pure\HTML\span;
            use function Pure\HTML\ul;

            return Compile::shape(
                div(
                    Slot::text('req'),
                    Slot::raw('body')->default(''),
                    p(Slot::text('opt')->default('d')),
                    Slot::if('flag', em('on'), em('off')),
                    ul(Slot::each('items', li(Slot::text('label')))->default([])),
                    Slot::child('meta', span(Slot::text('label')))->default(['label' => 'm'])
                )
                ->class(Slot::attr('cls')->default('c'))
                ->id(Slot::attr('ident')->default('i'))
                ->title(Slot::attr('tip')->default(null))
                ->hidden(Slot::attr('flagged')->default(false))
            );
            PHP);

        $artifact = self::load(ArtifactCompiler::write($file));
        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $this->assertInstanceOf(Renderer::class, $artifact);

        $flat = CodeGenerator::fromSource(
            CodeGenerator::source($shape->tree()),
            ShapeIndex::of($shape->tree())->id()
        );

        $stringable = new class () implements Stringable {
            public function __toString(): string
            {
                return 'an<b>&';
            }
        };

        // Every set leaves at least one accessor on its slow path: a missing
        // required slot, a present null, a default, a non-scalar attribute or a
        // value the artifact must validate before it can render it.
        $sets = [
            'all optional missing' => ['req' => 'R'],
            'required present as null' => ['req' => null],
            'attributes null, bool and int' => ['req' => 'R', 'cls' => null, 'tip' => false, 'flagged' => true, 'ident' => 7],
            'float attribute' => ['req' => 'R', 'tip' => 3.25],
            'stringable attribute' => ['req' => 'R', 'tip' => $stringable],
            'defaults used for scopes' => ['req' => 'R', 'items' => null, 'meta' => null],
            'nested scopes filled' => ['req' => 'R', 'items' => [['label' => 'a'], ['label' => 'b']], 'meta' => ['label' => 'M']],
            'nested item missing its slot' => ['req' => 'R', 'items' => [['other' => 1]]],
            'values are empty strings' => ['req' => '', 'opt' => '', 'cls' => ''],
            'condition slot true' => ['req' => 'R', 'flag' => true],
            'list is not iterable' => ['req' => 'R', 'items' => 'nope'],
            'raw joins an iterable' => ['req' => 'R', 'body' => ['a', Raw::of('<i>b</i>'), null]],
            'raw element must be stringable' => ['req' => 'R', 'body' => ['a', ['nested']]],
            'child is not a scope' => ['req' => 'R', 'meta' => 'nope'],
            'required slot missing' => [],
        ];

        foreach ($sets as $label => $set) {
            $this->assertSame(
                self::outcome(static fn (): string => $flat->render($set)),
                self::outcome(static fn (): string => $artifact->render($set)),
                $label
            );
        }
    }

    /**
     * The rendered markup, or the exception a renderer raises: both paths of a
     * slot accessor have to agree on failures as well as on bytes.
     *
     * @param callable(): string $render
     */
    private static function outcome(callable $render): string
    {
        try {
            return 'rendered:' . $render();
        } catch (Throwable $error) {
            return get_class($error) . ':' . $error->getMessage();
        }
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
                    Slot::if('flag', span('on'), span('off')),
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
        $this->assertStringContainsString('@var scalar|null|\\Stringable $title', $source);
        $this->assertStringContainsString('@var mixed $flag', $source);
        $this->assertStringContainsString(
            '@var iterable<array-key, array{class: scalar|null|\\Stringable, label: scalar|null|\\Stringable}> $items',
            $source
        );
        $this->assertLessThan(strpos($source, '?>'), strpos($source, '@var'));

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(CodeGenerator::source($shape->tree()), $index->id());

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

    public function testPlainViewsDeclareRootSlotsWithTypesDerivedFromTheShape(): void
    {
        $file = $this->shapeFile('plain-types.shape.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\div;
            use function Pure\HTML\em;
            use function Pure\HTML\li;
            use function Pure\HTML\ul;

            return Compile::shape(
                div(
                    Slot::text('title'),
                    Slot::child(
                        'content',
                        div(Slot::text('heading'), Slot::each('items', li(Slot::text('label'))))
                    ),
                    ul(Slot::each('links', li(Slot::text('label')))),
                    Slot::if('flag', em('on'))
                )->class(Slot::attr('cardClass'))
            );
            PHP);

        $plain = (string)ArtifactCompiler::build($file, true);

        $this->assertStringContainsString(
            "/**\n"
            . " * @var scalar|null|\\Stringable \$cardClass\n"
            . " * @var scalar|null|\\Stringable \$title\n"
            . " * @var array{heading: scalar|null|\\Stringable, items: iterable<array-key, array{label: scalar|null|\\Stringable}>} \$content\n"
            . " * @var iterable<array-key, array{label: scalar|null|\\Stringable}> \$links\n"
            . " * @var mixed \$flag\n"
            . ' */',
            $plain
        );
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
        $this->assertStringContainsString('switch ($item2[\'kind\'] ?? null)', $plain);
        $this->assertStringContainsString("case 'link':", $plain);
        $this->assertStringContainsString(
            '@var array{items: iterable<array-key, array{value: scalar|null|\\Stringable}>} $meta',
            $plain
        );
        $this->assertStringContainsString(
            '@var iterable<array-key, array{kind?: \'text\'|\'link\', value: scalar|null|\\Stringable}> $blocks',
            $plain
        );

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(CodeGenerator::source($shape->tree()), $index->id());

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
        $this->assertStringContainsString(
            '@var array{\'user-name\': scalar|null|\\Stringable, data: scalar|null|\\Stringable, v1: scalar|null|\\Stringable} $data',
            $plain
        );

        $shape = self::load($file);
        $this->assertInstanceOf(Shape::class, $shape);
        $index = ShapeIndex::of($shape->tree());
        $flat = CodeGenerator::fromSource(CodeGenerator::source($shape->tree()), $index->id());

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
        $this->expectExceptionMessage('is not a *.shape.php or *.cmp.php file');

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

        $compiled = $this->runCommand($command, ['pure', 'compile', '--plain', $file]);
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

    public function testWriteChangedSkipsFilesThatAreAlreadyCurrent(): void
    {
        $file = $this->shapeFile('incremental.shape.php', "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('a'));\n");

        $first = ArtifactCompiler::writeChanged($file, true);
        $this->assertTrue($first['artifactWritten']);
        $this->assertTrue($first['plainWritten']);

        $second = ArtifactCompiler::writeChanged($file, true);
        $this->assertFalse($second['artifactWritten']);
        $this->assertFalse($second['plainWritten']);

        file_put_contents($file, "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('b'));\n");

        $third = ArtifactCompiler::writeChanged($file, true);
        $this->assertTrue($third['artifactWritten']);
        $this->assertTrue($third['plainWritten']);

        $renderer = self::load($third['artifact']);
        $this->assertInstanceOf(Renderer::class, $renderer);
        $this->assertSame('<div>b</div>', $renderer->render([]));
    }

    public function testSecondCompileRunReportsUnchangedFiles(): void
    {
        $file = $this->shapeFile('twice.shape.php', "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('x'));\n");
        $command = new ArtifactCommand();

        $first = $this->runCommand($command, ['pure', 'compile', $file]);
        $this->assertSame(0, $first['code']);
        $this->assertStringContainsString('compiled:', $first['stdout']);

        $second = $this->runCommand($command, ['pure', 'compile', $file]);
        $this->assertSame(0, $second['code']);
        $this->assertStringContainsString('unchanged:', $second['stdout']);
        $this->assertStringNotContainsString('compiled:', $second['stdout']);
    }

    public function testTwoFilesClaimingOneArtifactAreBothRejected(): void
    {
        $shapeFile = $this->shapeFile(
            'box.shape.php',
            "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::text('title')));\n"
        );
        $unit = $this->unitFile('box.cmp.php', 'Box', 'component');

        $run = $this->runCommand($this->registryCommand(), ['pure', 'compile', $this->dir]);

        $this->assertSame(1, $run['code']);
        $this->assertStringContainsString('is claimed by both', $run['stderr']);
        $this->assertStringContainsString($shapeFile, $run['stderr']);
        $this->assertStringContainsString($unit, $run['stderr']);

        // Neither file wins by discovery order: the target is left unwritten.
        $this->assertStringNotContainsString('compiled:', $run['stdout']);
        $this->assertFileDoesNotExist($this->dir . '/box.pure.php');
    }

    public function testArtifactOfAnotherCacheVersionIsRejectedWhenLoaded(): void
    {
        $file = $this->shapeFile(
            'guarded.shape.php',
            "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::text('v')));\n"
        );
        $artifact = ArtifactCompiler::write($file);

        $this->assertInstanceOf(Renderer::class, self::load($artifact));

        // The guard line is what another version of the library would have left
        // behind; loading it must say `pure compile`, not fail on the signature.
        $contents = (string) file_get_contents($artifact);
        $foreign = str_replace(
            'if (' . Compile::CACHE_VERSION . ' !== ',
            'if (' . (Compile::CACHE_VERSION + 1) . ' !== ',
            $contents
        );
        $this->assertNotSame($contents, $foreign);
        file_put_contents($artifact, $foreign);

        try {
            self::load($artifact);
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('stale purephp artifact', $e->getMessage());
            $this->assertStringContainsString('run `pure compile` to rebuild', $e->getMessage());
        }
    }

    public function testCompilesUnitFilesWithAnExplicitShape(): void
    {
        $unit = $this->dir . '/badge.cmp.php';
        file_put_contents($unit, "<?php\n\n// unit placeholder: the shape is passed explicitly.\n");

        $shape = Compile::shape(\Pure\HTML\div(\Pure\Core\Slot::text('title')));
        $written = ArtifactCompiler::writeUnit($unit, $shape, true);

        $this->assertSame($this->dir . '/badge.pure.php', $written['artifact']);
        $this->assertSame($this->dir . '/badge.plain.php', $written['plain']);
        $this->assertTrue($written['artifactWritten']);
        $this->assertTrue($written['plainWritten']);

        $renderer = self::load($written['artifact']);
        $this->assertInstanceOf(Renderer::class, $renderer);
        $this->assertSame('<div>a</div>', $renderer->render(['title' => 'a']));

        $again = ArtifactCompiler::writeUnit($unit, $shape, true);
        $this->assertFalse($again['artifactWritten']);
        $this->assertFalse($again['plainWritten']);
    }

    public function testUnitArtifactsMatchShapeFileArtifacts(): void
    {
        $shapeFile = $this->shapeFile(
            'same.shape.php',
            "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::text('title')));\n"
        );
        // A distinct base name: `same.shape.php` and `same.cmp.php` would share
        // one artifact and the comparison below would read the same file twice.
        $unit = $this->dir . '/same-unit.cmp.php';
        file_put_contents($unit, "<?php\n\n// unit placeholder: the shape is passed explicitly.\n");

        $fromShapeFile = self::load(ArtifactCompiler::write($shapeFile));
        $fromUnit = self::load(
            ArtifactCompiler::writeUnit($unit, Compile::shape(\Pure\HTML\div(\Pure\Core\Slot::text('title'))))['artifact']
        );

        $this->assertInstanceOf(Renderer::class, $fromShapeFile);
        $this->assertInstanceOf(Renderer::class, $fromUnit);
        $this->assertSame($fromShapeFile->id, $fromUnit->id);
        $this->assertSame($fromShapeFile->render(['title' => 'a']), $fromUnit->render(['title' => 'a']));
    }

    public function testCompilesUnitFilesThroughTheCommand(): void
    {
        $file = $this->unitFile('badge.cmp.php', 'Badge', 'component');
        $command = $this->registryCommand();

        $compiled = $this->runCommand($command, ['pure', 'compile', '--plain', $file]);
        $this->assertSame(0, $compiled['code']);
        $this->assertStringContainsString('compiled:', $compiled['stdout']);
        $this->assertFileExists($this->dir . '/badge.pure.php');
        $this->assertFileExists($this->dir . '/badge.plain.php');

        $fresh = $this->runCommand($command, ['pure', 'compile', '--check', '--plain', $file]);
        $this->assertSame(0, $fresh['code']);
        $this->assertStringContainsString('up to date:', $fresh['stdout']);

        $list = $this->runCommand($command, ['pure', 'compile', '--list', $file]);
        $this->assertSame(0, $list['code']);
        $this->assertStringContainsString("Badge -> {$file} (component)", $list['stdout']);

        file_put_contents($this->dir . '/badge.pure.php', "<?php\n// stale\n");

        $stale = $this->runCommand($command, ['pure', 'compile', '--check', $file]);
        $this->assertSame(1, $stale['code']);
        $this->assertStringContainsString('stale:', $stale['stdout']);
    }

    public function testUnitFilesNeedTheRegistry(): void
    {
        $file = $this->unitFile('badge.cmp.php', 'Badge', 'component');

        $result = $this->runCommand(new ArtifactCommand(), ['pure', 'compile', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString('need the component registry', $result['stderr']);
    }

    public function testUnitFileMustRegisterExactlyOneUnit(): void
    {
        $command = $this->registryCommand();

        $empty = $this->unitFile('empty.cmp.php', null, 'component');
        $none = $this->runCommand($command, ['pure', 'compile', $empty]);
        $this->assertSame(1, $none['code']);
        $this->assertStringContainsString('no component unit is registered', $none['stderr']);

        $two = $this->dir . '/two.cmp.php';
        file_put_contents($two, <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\span;

            register('One', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(span(Slot::text('label'))));
            register('Two', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(span(Slot::text('label'))));
            PHP);

        $many = $this->runCommand($command, ['pure', 'compile', $two]);
        $this->assertSame(1, $many['code']);
        $this->assertStringContainsString("already registered as 'One'", $many['stderr']);
    }

    public function testCommandRejectsResolversThatReturnSeveralUnits(): void
    {
        $file = $this->unitFile('badge.cmp.php', 'Badge', 'component');
        $shape = Compile::shape(\Pure\HTML\span(\Pure\Core\Slot::text('label')));
        $command = new ArtifactCommand(static fn (string $path): array => [
            'One' => ['factory' => static fn (): Shape => $shape, 'document' => false],
            'Two' => ['factory' => static fn (): Shape => $shape, 'document' => false],
        ]);

        $result = $this->runCommand($command, ['pure', 'compile', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString('2 component units are registered here', $result['stderr']);
    }

    public function testListReportsShapeFiles(): void
    {
        $file = $this->shapeFile('plain.shape.php', "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div('x'));\n");

        $list = $this->runCommand($this->registryCommand(), ['pure', 'compile', '--list', $file]);

        $this->assertSame(0, $list['code']);
        $this->assertStringContainsString("{$file} (shape)", $list['stdout']);
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
        $this->assertStringContainsString('is not a *.shape.php or *.cmp.php file', $suffix['stderr']);

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

    private function registryCommand(): ArtifactCommand
    {
        return new ArtifactCommand(static fn (string $file): array => Registry::unitsFor($file));
    }

    /**
     * Write a `*.cmp.php` file that registers one unit.
     */
    private function unitFile(string $name, ?string $component, string $kind): string
    {
        $file = $this->dir . '/' . $name;
        $registerFunction = $kind === 'page' ? 'registerPage' : 'register';
        $register = $component === null
            ? ''
            : $registerFunction . "('{$component}', __FILE__, static fn (): \\Pure\\Compile\\Shape => Compile::shape(span(Slot::text('label'))));";

        file_put_contents($file, <<<PHP
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\{$registerFunction};
            use function Pure\HTML\span;

            {$register}
            PHP);

        return $file;
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
