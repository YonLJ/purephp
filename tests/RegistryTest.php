<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/UnitFactory.php';
require_once __DIR__ . '/Support/ModernUnit.php';

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

    private static int $unitCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/purephp-registry-' . bin2hex(random_bytes(6));
        mkdir($this->dir, 0o700, true);

        Compile::cachePath(null);
        Compile::flush();
        Registry::reset();
        UnitFactory::reset();
    }

    protected function tearDown(): void
    {
        Registry::reset();
        UnitFactory::reset();
        $this->remove($this->dir);

        Compile::flush();

        parent::tearDown();
    }

    public function testRendersARegisteredComponentByName(): void
    {
        [$name] = $this->unit('Badge', fn (): Shape => $this->badgeShape());

        $this->assertSame('<span>x</span>', $this->renderUnit($name, ['label' => 'x']));
        $this->assertSame('<span>y</span>', $this->renderUnit($name, ['label' => 'y']));
    }

    public function testFactoryReturningABareTagTreeIsWrapped(): void
    {
        [$name] = $this->unit('Badge', fn () => span(Slot::value('label')));

        $this->assertSame('<span>x</span>', $this->renderUnit($name, ['label' => 'x']));
    }

    public function testShapeFileReturningABareTagTreeIsWrapped(): void
    {
        $file = $this->dir . '/bare.shape.php';
        file_put_contents($file, "<?php\n\nreturn Pure\\HTML\\span(Pure\\Core\\Slot::value('label'));\n");

        $this->assertSame('<span>x</span>', $this->renderUnit($file, ['label' => 'x']));
    }

    public function testNameAndPathResolveToTheSameBinder(): void
    {
        [$name, $file] = $this->unit('Badge', fn (): Shape => $this->badgeShape());

        $this->assertSame(Registry::component($name), Registry::component($file));
        $this->assertSame('<span>x</span>', $this->renderUnit($file, ['label' => 'x']));
    }

    public function testFactoryIsLazyWhenTheArtifactIsFresh(): void
    {
        $calls = 0;
        [$name, $file] = $this->unit('Badge', function () use (&$calls): Shape {
            $calls++;

            return $this->badgeShape();
        });

        ArtifactCompiler::writeUnit($file, $this->badgeShape());
        clearstatcache();

        $this->assertSame('<span>x</span>', $this->renderUnit($name, ['label' => 'x']));
        $this->assertSame(0, $calls);
    }

    public function testFactoryRunsOncePerGenerationWithoutAnArtifact(): void
    {
        $calls = 0;
        [$name] = $this->unit('Badge', function () use (&$calls): Shape {
            $calls++;

            return $this->badgeShape();
        });

        $this->renderUnit($name, ['label' => 'a']);
        $this->renderUnit($name, ['label' => 'b']);
        $this->assertSame(1, $calls);

        Compile::flush();

        $this->renderUnit($name, ['label' => 'c']);
        $this->assertSame(2, $calls);
    }

    public function testFactoryMustReturnAShape(): void
    {
        [$name] = $this->unit('Badge', static fn (): mixed => 'not a shape');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return a tag tree or Pure\\Compile\\Shape');

        $this->renderUnit($name, ['label' => 'x']);
    }

    public function testRegisteringTheSameNameForTheSameFileIsIdempotent(): void
    {
        [$name] = $this->unit('Badge', fn (): Shape => $this->badgeShape());
        UnitFactory::set($name . '#second', fn (): Shape => Compile::shape(span('other')));
        register(
            self::callOf($name),
            factory: static fn (): mixed => UnitFactory::run($name . '#second'),
        );

        $this->assertSame('<span>x</span>', $this->renderUnit($name, ['label' => 'x']));
    }

    public function testDuplicateNameThrowsUnlessOverridden(): void
    {
        // The public register() derives name and file from one call function,
        // so two files cannot claim a name: only the internal primitive can,
        // which is what this invariant guards.
        $first = $this->placeholderFile('First.cmp.php');
        $second = $this->placeholderFile('Second.cmp.php');
        $name = 'BadgeShared' . self::unique();

        Registry::register($name, $first, fn (): Shape => $this->badgeShape());

        try {
            Registry::register($name, $second, fn (): Shape => $this->badgeShape());
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("already registered by '{$first}'", $e->getMessage());
        }

        Registry::register($name, $second, fn (): Shape => Compile::shape(span('override')), override: true);

        $this->assertSame('<span>override</span>', $this->renderUnit($name, ['label' => 'x']));
        $this->assertSame([], Registry::unitsFor($first));
    }

    public function testOneFileRegistersOneUnit(): void
    {
        [$registeredName, $otherName] = $this->unitPair('Badge');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('a unit file registers one component');

        // The second call function lives in the same unit file, so the public
        // API reaches the same file-owned-by-one-name invariant.
        register(self::callOf($otherName), factory: static fn (): mixed => UnitFactory::run($otherName));
    }

    public function testUnknownNameListsKnownComponents(): void
    {
        [$name] = $this->unit('Badge', fn (): Shape => $this->badgeShape());

        try {
            $this->renderUnit('Nope');
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("unknown component 'Nope'", $e->getMessage());
            $this->assertStringContainsString("known components: {$name}", $e->getMessage());
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
        [$name] = $this->unit('Page', fn (): Shape => Compile::shape(html(body(Slot::value('title')))));

        // No page concept: the tree renders as written, the header is the
        // caller's to prepend.
        $body = $this->renderUnit($name, ['title' => 'a']);
        $this->assertSame('<html><body>a</body></html>', $body);
        $this->assertSame('<!DOCTYPE html><html><body>a</body></html>', '<!DOCTYPE html>' . $body);
    }

    public function testUnitsForReportsTheUnitsOfAFile(): void
    {
        [$firstName, $firstFile] = $this->unit('Badge', fn (): Shape => $this->badgeShape());
        [$secondName] = $this->unit('Page', fn (): Shape => Compile::shape(html(body())));

        $units = Registry::unitsFor($firstFile);

        $this->assertSame([$firstName], array_keys($units));
        $this->assertArrayHasKey('factory', $units[$firstName]);
        $this->assertSame([$firstName, $secondName], Registry::names());
    }

    public function testResetClearsRegistrations(): void
    {
        [$name] = $this->unit('Badge', fn (): Shape => $this->badgeShape());

        Registry::reset();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("unknown component '{$name}'");

        $this->renderUnit($name);
    }

    public function testBareShapePathStillRenders(): void
    {
        $file = $this->dir . '/bare-page.shape.php';
        file_put_contents(
            $file,
            "<?php\n\nreturn Pure\\Compile\\Compile::shape(Pure\\HTML\\div(Pure\\Core\\Slot::value('title')));\n"
        );

        $this->assertSame('<div>x</div>', $this->renderUnit($file, ['title' => 'x']));
    }

    public function testAnUnregisteredUnitPathIsNotLoadedAsATemplate(): void
    {
        $file = $this->dir . '/Loose.cmp.php';
        $content = <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Component\Call;

            use function Pure\Component\{component, register};

            if (!function_exists('Loose')) {
                function Loose(mixed ...$children): Call
                {
                    return component(__FUNCTION__, ...$children);
                }
            }

            register(Loose(...),
                factory: static fn (): mixed => \UnitFactory::run('Loose'),
            );
            PHP;
        file_put_contents($file, $content);
        UnitFactory::set('Loose', static fn (): Shape => Compile::shape(\Pure\HTML\em('l')));

        // Loading the file registers the unit, so a path render cannot be
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
        [$name] = $this->unit('Badge', fn (): Shape => $this->badgeShape());

        try {
            $this->renderUnit('ui/Bdge');
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("component template 'ui/Bdge' does not exist", $e->getMessage());
            $this->assertStringContainsString("known components: {$name}", $e->getMessage());
        }
    }

    public function testAnArtifactOfTheSameSecondServesTheUnit(): void
    {
        // The binder compares mtimes at whole-second granularity, which is how a
        // `pure compile` that runs in the same second as the last source edit
        // still serves its artifact. An edit that lands in a later second is
        // what makes the unit recompile.
        $calls = 0;
        [$name, $file] = $this->unit('Badge', function () use (&$calls): Shape {
            $calls++;

            return Compile::shape(div(Slot::value('label')));
        });

        ArtifactCompiler::writeUnit($file, $this->badgeShape());
        clearstatcache();
        $this->assertSame(filemtime($file), filemtime(ArtifactCompiler::artifactPath($file)));

        $this->assertSame('<span>x</span>', $this->renderUnit($name, ['label' => 'x']));
        $this->assertSame(0, $calls);

        touch($file, (int) filemtime($file) + 1);
        clearstatcache();
        Compile::flush();

        $this->assertSame('<div>x</div>', $this->renderUnit($name, ['label' => 'x']));
        $this->assertSame(1, $calls);
    }

    public function testAnEmptyNameIsRejected(): void
    {
        // The public register() derives a non-empty name from the call
        // function; only the internal primitive can be handed an empty one.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('component name must not be empty');

        Registry::register('', $this->placeholderFile('Badge.cmp.php'), fn (): Shape => $this->badgeShape());
    }

    public function testAUnitFileMustExist(): void
    {
        // The public register() derives the file from a real call function;
        // only the internal primitive can point at a missing one.
        $missing = $this->dir . '/Missing.cmp.php';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("unit file '{$missing}' does not exist");

        Registry::register('BadgeMissing' . self::unique(), $missing, fn (): Shape => $this->badgeShape());
    }

    public function testOverrideReapsTheNameThatOwnedTheFile(): void
    {
        [$first, $second] = $this->unitPair('Badge');

        register(self::callOf($second), factory: static fn (): mixed => UnitFactory::run($second), override: true);

        // One file registers one component: the replaced name is gone entirely.
        $this->assertSame([$second], Registry::names());
        $file = $this->dir . '/' . $first . '.cmp.php';
        $this->assertSame([$second], array_keys(Registry::unitsFor($file)));
        $this->assertSame('<div>x</div>', $this->renderUnit($second, ['label' => 'x']));

        try {
            $this->renderUnit($first, ['label' => 'x']);
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString("unknown component '{$first}'", $e->getMessage());
        }
    }

    /**
     * Load a fresh unit in the recommended form: the generated file holds the
     * call function and register(Name(...)), and the test supplies the factory.
     *
     * @param callable(): mixed $factory
     * @return array{0: string, 1: string} The unique component name and its unit file.
     */
    private function unit(string $base, callable $factory): array
    {
        $name = $base . 'R' . self::unique();
        UnitFactory::set($name, $factory);
        $file = ModernUnit::load($this->dir . '/' . $name . '.cmp.php', $name);

        return [$name, $file];
    }

    /**
     * A unit file that defines two call functions but registers only the
     * first, so a test can register the second through the public API against
     * the same file.
     *
     * @param callable(): mixed $firstFactory
     * @param callable(): mixed $secondFactory
     * @return array{0: string, 1: string} The registered name and the unregistered one.
     */
    private function unitPair(string $base, ?callable $firstFactory = null, ?callable $secondFactory = null): array
    {
        $first = $base . 'A' . self::unique();
        $second = $base . 'B' . self::unique();
        UnitFactory::set($first, $firstFactory ?? (fn (): Shape => $this->badgeShape()));
        UnitFactory::set($second, $secondFactory ?? (fn (): Shape => Compile::shape(div(Slot::value('label')))));
        $file = $this->dir . '/' . $first . '.cmp.php';

        file_put_contents($file, <<<PHP
            <?php

            declare(strict_types=1);

            use Pure\Component\Call;

            use function Pure\Component\{component, register};

            if (!function_exists('{$first}')) {
                function {$first}(mixed ...\$children): Call
                {
                    return component(__FUNCTION__, ...\$children);
                }
            }

            if (!function_exists('{$second}')) {
                function {$second}(mixed ...\$children): Call
                {
                    return component(__FUNCTION__, ...\$children);
                }
            }

            register({$first}(...),
                factory: static fn (): mixed => \UnitFactory::run('{$first}'),
            );
            PHP);

        if (!in_array($first, Registry::names(), true)) {
            include $file;
        }

        return [$first, $second];
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

    /**
     * A declared call function as register() takes it: the generated unit
     * files declare the functions, and PHPStan cannot see their names.
     */
    private static function callOf(string $name): Closure
    {
        if (!is_callable($name)) {
            throw new InvalidArgumentException("the unit file must declare {$name}()");
        }

        return Closure::fromCallable($name);
    }

    private function placeholderFile(string $name): string
    {
        $file = $this->dir . '/' . $name;
        file_put_contents($file, "<?php\n\n// placeholder: the test registers the factory directly.\n");

        return $file;
    }

    private static function unique(): string
    {
        return (string) self::$unitCounter++ . bin2hex(random_bytes(2));
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
