<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Component\Registry;

use function Pure\Component\render;

use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\HTML\div;

class ComponentTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/purephp-component-' . bin2hex(random_bytes(6));
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

    public function testInlineTreeBindsDataToString(): void
    {
        $render = Compile::shape(div(Slot::value('title')))->compile();

        $this->assertSame('<div>a &amp; b</div>', $render->render(['title' => 'a & b']));
    }

    public function testRawAndStringableSlotValuesAreCoercedWithoutACast(): void
    {
        $render = Compile::shape(div(Slot::raw('content')))->compile();

        // A Raw (trusted markup) is emitted verbatim in a raw slot, no cast.
        $this->assertSame(
            '<div><b>x</b></div>',
            $render->render(['content' => Raw::of('<b>x</b>')])
        );

        // Any Stringable is coerced the same way.
        $stringable = new class () {
            public function __toString(): string
            {
                return '<i>y</i>';
            }
        };
        $this->assertSame(
            '<div><i>y</i></div>',
            $render->render(['content' => $stringable])
        );
    }

    public function testRawSlotJoinsALocalListOfComponentMarkup(): void
    {
        // A raw slot accepts a list of Raw / Stringable markup, emitted verbatim
        // and concatenated, so a rendered list needs no intermediate implode().
        $render = Compile::shape(div(Slot::raw('navs')))->compile();

        $navs = [Raw::of('<a>a</a>'), Raw::of('<b>b</b>')];
        $this->assertSame(
            '<div><a>a</a><b>b</b></div>',
            $render->render(['navs' => $navs])
        );
    }

    public function testRawValueInATextSlotIsEscaped(): void
    {
        // raw() is the verbatim path; a Raw handed to a value slot is still
        // stringified and escaped, keeping the safe default.
        $render = Compile::shape(div(Slot::value('content')))->compile();

        $this->assertSame(
            '<div>&lt;b&gt;x&lt;/b&gt;</div>',
            $render->render(['content' => Raw::of('<b>x</b>')])
        );
    }

    public function testShapeFileCompilesWhenThereIsNoArtifact(): void
    {
        $file = $this->shapeFile('badge.shape.php', 'div');
        $shape = require $file;

        $this->assertSame('<div>b</div>', $shape->compile()->render(['title' => 'b']));
    }

    public function testFreshArtifactIsLoadedInsteadOfTheShapeFile(): void
    {
        $file = $this->shapeFile('badge.shape.php', 'div');
        touch($file, time() - 60);
        clearstatcache();

        ArtifactCompiler::write($file);
        clearstatcache();

        // The artifact is newer than the shape file, so it wins even after the
        // shape file changes: only `pure compile` makes the change visible.
        file_put_contents($file, $this->shapeSource('span'));
        touch($file, time() - 60);
        clearstatcache();

        $shape = Registry::component($file)(['title' => 'b']);
        $this->assertSame('<div>b</div>', $shape);

        // Touching the shape file makes it newer, so it compiles again once
        // the binder cache is dropped: binders are cached per compile
        // generation, and Compile::flush() starts a new one.
        touch($file);
        touch(ArtifactCompiler::artifactPath($file), time() - 60);
        clearstatcache();
        Compile::flush();

        $shape = Registry::component($file)(['title' => 'b']);
        $this->assertSame('<span>b</span>', $shape);
    }

    public function testRenderPassesNamedArgumentsAsSlots(): void
    {
        $file = $this->shapeFile('badge.shape.php', 'div');

        $this->assertSame('<div>a &amp; b</div>', render($file, title: 'a & b'));
    }

    public function testRenderAcceptsAnUnpackedBindingsArray(): void
    {
        $file = $this->shapeFile('badge.shape.php', 'div');

        $this->assertSame('<div>b</div>', render($file, ...['title' => 'b']));
    }

    public function testRenderRejectsPositionalData(): void
    {
        $file = $this->shapeFile('badge.shape.php', 'div');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('passed by name');

        render($file, ['title' => 'b']);
    }

    public function testRenderDoesNotPrependTheDocumentHeader(): void
    {
        $file = $this->dir . '/page.shape.php';
        file_put_contents($file, <<<PHP
            <?php

            declare(strict_types=1);

            use Pure\\Compile\\Compile;
            use Pure\\Core\\Slot;

            use function Pure\\HTML\\{body, html};

            return Compile::shape(html(body(Slot::value('title'))));
            PHP);

        // render() emits the tree as written; the document header is the
        // caller's to prepend.
        $this->assertSame(
            '<html><body>a</body></html>',
            render($file, title: 'a')
        );
    }

    public function testMissingTemplateThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        render($this->dir . '/missing.shape.php');
    }

    public function testTemplateThatDoesNotReturnAShapeThrows(): void
    {
        $file = $this->dir . '/bad.shape.php';
        file_put_contents($file, "<?php\n\nreturn 42;\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return a tag tree or Pure\\Compile\\Shape');

        render($file);
    }

    public function testArtifactThatDoesNotReturnARendererThrows(): void
    {
        $file = $this->shapeFile('bad-artifact.shape.php', 'div');
        touch($file, time() - 60);
        clearstatcache();

        file_put_contents(ArtifactCompiler::artifactPath($file), "<?php\n\nreturn 42;\n");
        clearstatcache();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return a Renderer');

        render($file);
    }

    private function shapeFile(string $name, string $tag): string
    {
        $file = $this->dir . '/' . $name;
        file_put_contents($file, $this->shapeSource($tag));

        return $file;
    }

    private function shapeSource(string $tag): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            use Pure\\Compile\\Compile;
            use Pure\\Core\\Slot;

            use function Pure\\HTML\\{$tag};

            return Compile::shape({$tag}(Slot::value('title')));
            PHP;
    }

    private function remove(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                @unlink($dir . '/' . $entry);
            }
        }

        @rmdir($dir);
    }
}
