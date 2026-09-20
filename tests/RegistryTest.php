<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Compile\Shape;

use function Pure\Component\component;
use function Pure\Component\register;

use Pure\Component\Registry;
use Pure\Core\Slot;

use function Pure\HTML\body;
use function Pure\HTML\div;
use function Pure\HTML\html;
use function Pure\HTML\span;

class RegistryTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/purephp-registry-' . bin2hex(random_bytes(6));
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

    public function testRendersARegisteredComponentByName(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        $this->assertSame('<span>x</span>', $this->renderUnit('Badge', ['label' => 'x']));
        $this->assertSame('<span>y</span>', $this->renderUnit('Badge', ['label' => 'y']));
    }

    public function testFactoryReturningABareTagTreeIsWrapped(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn () => span(Slot::value('label')));

        $this->assertSame('<span>x</span>', $this->renderUnit('Badge', ['label' => 'x']));
    }

    public function testShapeFileReturningABareTagTreeIsWrapped(): void
    {
        $file = $this->dir . '/bare.shape.php';
        file_put_contents($file, "<?php\n\nreturn Pure\\HTML\\span(Pure\\Core\\Slot::value('label'));\n");

        $this->assertSame('<span>x</span>', $this->renderUnit($file, ['label' => 'x']));
    }

    public function testNameAndPathResolveToTheSameBinder(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        $this->assertSame(Registry::component('Badge'), Registry::component($file));
        $this->assertSame('<span>x</span>', $this->renderUnit($file, ['label' => 'x']));
    }

    public function testFactoryIsLazyWhenTheArtifactIsFresh(): void
    {
        $calls = 0;
        $file = $this->unitFile();
        register('Badge', $file, function () use (&$calls): Shape {
            $calls++;

            return $this->badgeShape();
        });

        ArtifactCompiler::writeUnit($file, $this->badgeShape());
        clearstatcache();

        $this->assertSame('<span>x</span>', $this->renderUnit('Badge', ['label' => 'x']));
        $this->assertSame(0, $calls);
    }

    public function testFactoryRunsOncePerGenerationWithoutAnArtifact(): void
    {
        $calls = 0;
        $file = $this->unitFile();
        register('Badge', $file, function () use (&$calls): Shape {
            $calls++;

            return $this->badgeShape();
        });

        $this->renderUnit('Badge', ['label' => 'a']);
        $this->renderUnit('Badge', ['label' => 'b']);
        $this->assertSame(1, $calls);

        Compile::flush();

        $this->renderUnit('Badge', ['label' => 'c']);
        $this->assertSame(2, $calls);
    }

    public function testFactoryMustReturnAShape(): void
    {
        $file = $this->unitFile();

        register('Badge', $file, static fn (): mixed => 'not a shape');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return a tag tree or Pure\\Compile\\Shape');

        $this->renderUnit('Badge', ['label' => 'x']);
    }

    public function testRegisteringTheSameNameForTheSameFileIsIdempotent(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());
        register('Badge', $file, fn (): Shape => Compile::shape(span('other')));

        $this->assertSame('<span>x</span>', $this->renderUnit('Badge', ['label' => 'x']));
    }

    public function testDuplicateNameThrowsUnlessOverridden(): void
    {
        $first = $this->unitFile('First.cmp.php');
        $second = $this->unitFile('Second.cmp.php');
        register('Badge', $first, fn (): Shape => $this->badgeShape());

        try {
            register('Badge', $second, fn (): Shape => $this->badgeShape());
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("already registered by '{$first}'", $e->getMessage());
        }

        register('Badge', $second, fn (): Shape => Compile::shape(span('override')), override: true);

        $this->assertSame('<span>override</span>', $this->renderUnit('Badge', ['label' => 'x']));
        $this->assertSame([], Registry::unitsFor($first));
    }

    public function testOneFileRegistersOneUnit(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('a unit file registers one component');

        register('Other', $file, fn (): Shape => $this->badgeShape());
    }

    public function testUnknownNameListsKnownComponents(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        try {
            $this->renderUnit('Nope');
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("unknown component 'Nope'", $e->getMessage());
            $this->assertStringContainsString('known components: Badge', $e->getMessage());
        }
    }

    public function testUnknownNameWithoutRegistrations(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no components are registered');

        $this->renderUnit('Nope');
    }

    public function testRenderDoesNotPrependTheDocumentHeader(): void
    {
        $file = $this->unitFile('Page.cmp.php');
        register('Page', $file, fn (): Shape => Compile::shape(html(body(Slot::value('title')))));

        // No page concept: the tree renders as written, the header is the
        // caller's to prepend.
        $body = $this->renderUnit('Page', ['title' => 'a']);
        $this->assertSame('<html><body>a</body></html>', $body);
        $this->assertSame('<!DOCTYPE html><html><body>a</body></html>', '<!DOCTYPE html>' . $body);
    }

    public function testUnitsForReportsTheUnitsOfAFile(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());
        register('Page', $this->unitFile('Page.cmp.php'), fn (): Shape => Compile::shape(html(body())));

        $units = Registry::unitsFor($file);

        $this->assertSame(['Badge'], array_keys($units));
        $this->assertArrayHasKey('factory', $units['Badge']);
        $this->assertSame(['Badge', 'Page'], Registry::names());
    }

    public function testResetClearsRegistrations(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        Registry::reset();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("unknown component 'Badge'");

        $this->renderUnit('Badge');
    }

    public function testBareShapePathStillRenders(): void
    {
        $file = $this->dir . '/legacy.shape.php';
        file_put_contents(
            $file,
            "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::value('title')));\n"
        );

        $this->assertSame('<div>x</div>', $this->renderUnit($file, ['title' => 'x']));
    }

    public function testAnUnregisteredUnitPathIsNotLoadedAsATemplate(): void
    {
        $file = $this->dir . '/Loose.cmp.php';
        file_put_contents(
            $file,
            "<?php\n\nPure\\Component\\register('Loose', __FILE__,"
                . " static fn (): \\Pure\\Compile\\Shape =>"
                . " \\Pure\\Compile\\Compile::shape(\\Pure\\HTML\\em('l')));\n"
        );

        // Requiring the file registers the unit, so a path render cannot be
        // allowed to fail with "must return a Shape" and then succeed.
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $this->renderUnit($file, ['label' => 'x']);
                $this->fail('Expected RuntimeException to be thrown.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('is a component unit and has no fresh artifact', $e->getMessage());
            }
        }

        $this->assertSame([], Registry::names());

        require_once $file;

        $this->assertSame(['Loose'], Registry::names());
        $this->assertSame('<em>l</em>', $this->renderUnit('Loose'));
    }

    public function testAPathLikeTypoListsKnownComponents(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        try {
            $this->renderUnit('ui/Bdge');
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("component template 'ui/Bdge' does not exist", $e->getMessage());
            $this->assertStringContainsString('known components: Badge', $e->getMessage());
        }
    }

    public function testAnArtifactOfTheSameSecondServesTheUnit(): void
    {
        // The binder compares mtimes at whole-second granularity, which is how a
        // `pure compile` that runs in the same second as the last source edit
        // still serves its artifact. An edit that lands in a later second is
        // what makes the unit recompile.
        $file = $this->unitFile();
        $calls = 0;
        register('Badge', $file, function () use (&$calls): Shape {
            $calls++;

            return Compile::shape(div(Slot::value('label')));
        });

        ArtifactCompiler::writeUnit($file, $this->badgeShape());
        clearstatcache();
        $this->assertSame(filemtime($file), filemtime(ArtifactCompiler::artifactPath($file)));

        $this->assertSame('<span>x</span>', $this->renderUnit('Badge', ['label' => 'x']));
        $this->assertSame(0, $calls);

        touch($file, (int) filemtime($file) + 1);
        clearstatcache();
        Compile::flush();

        $this->assertSame('<div>x</div>', $this->renderUnit('Badge', ['label' => 'x']));
        $this->assertSame(1, $calls);
    }

    public function testAnEmptyNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('component name must not be empty');

        register('', $this->unitFile(), fn (): Shape => $this->badgeShape());
    }

    public function testAUnitFileMustExist(): void
    {
        $missing = $this->dir . '/Missing.cmp.php';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("unit file '{$missing}' does not exist");

        register('Badge', $missing, fn (): Shape => $this->badgeShape());
    }

    public function testOverrideReapsTheNameThatOwnedTheFile(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());
        register('Label', $file, fn (): Shape => Compile::shape(div(Slot::value('label'))), override: true);

        // One file registers one component: the replaced name is gone entirely.
        $this->assertSame(['Label'], Registry::names());
        $this->assertSame(['Label'], array_keys(Registry::unitsFor($file)));
        $this->assertSame('<div>x</div>', $this->renderUnit('Label', ['label' => 'x']));

        try {
            $this->renderUnit('Badge', ['label' => 'x']);
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("unknown component 'Badge'", $e->getMessage());
        }
    }

    /**
     * Render one registered unit or template path through a fluent call: the
     * bindings are set as props.
     *
     * @param array<string, mixed> $props
     */
    private function renderUnit(string $nameOrPath, array $props = []): string
    {
        return component($nameOrPath)->props($props)->render();
    }

    private function unitFile(string $name = 'Badge.cmp.php'): string
    {
        $file = $this->dir . '/' . $name;
        file_put_contents($file, "<?php\n\n// placeholder: the test registers the factory directly.\n");

        return $file;
    }

    private function badgeShape(): Shape
    {
        return Compile::shape(span(Slot::value('label')));
    }

    private function remove(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
