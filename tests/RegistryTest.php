<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Compile\Shape;

use function Pure\Component\component;
use function Pure\Component\register;
use function Pure\Component\registerPage;

use Pure\Component\Registry;

use function Pure\Component\render;
use function Pure\Component\renderPage;

use Pure\Core\Slot;

use function Pure\HTML\body;
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

        $this->assertSame('<span>x</span>', (string)render('Badge', label: 'x'));
        $this->assertSame('<span>y</span>', (string)component('Badge')(['label' => 'y']));
    }

    public function testNameAndPathResolveToTheSameBinder(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        $this->assertSame(component('Badge'), component($file));
        $this->assertSame('<span>x</span>', (string)render($file, label: 'x'));
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

        $this->assertSame('<span>x</span>', (string)render('Badge', label: 'x'));
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

        render('Badge', label: 'a');
        render('Badge', label: 'b');
        $this->assertSame(1, $calls);

        Compile::flush();

        render('Badge', label: 'c');
        $this->assertSame(2, $calls);
    }

    public function testFactoryMustReturnAShape(): void
    {
        $file = $this->unitFile();

        register('Badge', $file, static fn (): mixed => 'not a shape');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return a Pure\\Compile\\Shape');

        render('Badge', label: 'x');
    }

    public function testRegisteringTheSameNameForTheSameFileIsIdempotent(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());
        register('Badge', $file, fn (): Shape => Compile::shape(span('other')));

        $this->assertSame('<span>x</span>', (string)render('Badge', label: 'x'));
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

        $this->assertSame('<span>override</span>', (string)render('Badge', label: 'x'));
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
            render('Nope');
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

        render('Nope');
    }

    public function testPageUnitPrependsTheDocumentHeader(): void
    {
        $file = $this->unitFile('Page.cmp.php');
        registerPage('Page', $file, fn (): Shape => Compile::shape(html(body(Slot::text('title')))));

        $this->assertSame(
            '<!DOCTYPE html><html><body>a</body></html>',
            (string)renderPage('Page', ['title' => 'a'])
        );
    }

    public function testUnitsForReportsTheUnitsOfAFile(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());
        registerPage('Page', $this->unitFile('Page.cmp.php'), fn (): Shape => Compile::shape(html(body())));

        $units = Registry::unitsFor($file);

        $this->assertSame(['Badge'], array_keys($units));
        $this->assertFalse($units['Badge']['document']);
        $this->assertSame(['Badge', 'Page'], Registry::names());
    }

    public function testResetClearsRegistrations(): void
    {
        $file = $this->unitFile();
        register('Badge', $file, fn (): Shape => $this->badgeShape());

        Registry::reset();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("unknown component 'Badge'");

        render('Badge');
    }

    public function testBareShapePathStillRenders(): void
    {
        $file = $this->dir . '/legacy.shape.php';
        file_put_contents(
            $file,
            "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::text('title')));\n"
        );

        $this->assertSame('<div>x</div>', (string)render($file, title: 'x'));
    }

    private function unitFile(string $name = 'Badge.cmp.php'): string
    {
        $file = $this->dir . '/' . $name;
        file_put_contents($file, "<?php\n\n// placeholder: the test registers the factory directly.\n");

        return $file;
    }

    private function badgeShape(): Shape
    {
        return Compile::shape(span(Slot::text('label')));
    }

    private function remove(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
