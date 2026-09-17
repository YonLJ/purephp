<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ShapeGuard;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\li;
use function Pure\HTML\span;
use function Pure\HTML\ul;

class CompileCacheTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/purephp-cache-' . bin2hex(random_bytes(6));
        Compile::cachePath($this->dir);
        Compile::flush();
    }

    protected function tearDown(): void
    {
        Compile::clearCache();
        Compile::cachePath(null);
        Compile::flush();

        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        if ($this->dir !== '' && is_dir($this->dir)) {
            @rmdir($this->dir);
        }

        parent::tearDown();
    }

    public function testIdIsStableAndStructureSensitive(): void
    {
        $a = Compile::shape(div(span('x'))->class('c'));
        $b = Compile::shape(div(span('x'))->class('c'));
        $c = Compile::shape(div(span('y'))->class('c'));

        $this->assertSame(40, strlen($a->id()));
        $this->assertSame($a->id(), $b->id());
        $this->assertNotSame($a->id(), $c->id());
    }

    public function testCacheWritesAndReloadsByteIdentically(): void
    {
        $shape = Compile::shape(div(span(Slot::text('v')))->class('c'));
        $first = $shape(['v' => 'a & b']);
        $source = $shape->compile()->source;
        $file = $this->dir . '/' . $shape->id() . '.php';

        $this->assertFileExists($file);

        Compile::flush();

        $reloaded = Compile::shape(div(span(Slot::text('v')))->class('c'));
        $second = $reloaded(['v' => 'a & b']);

        $this->assertSame($first, $second);
        $this->assertSame($shape->id(), $reloaded->id());
        $this->assertSame($source, $reloaded->compile()->source);
    }

    public function testCacheReloadsShapesWithMapsByteIdentically(): void
    {
        $item = Compile::shape(li(Slot::text('label')));
        $shape = $this->mappedListShape($item);
        $first = $shape(['items' => ['a', 'b']]);
        $source = $shape->compile()->source;
        $file = $this->dir . '/' . $shape->id() . '.php';

        $this->assertFileExists($file);

        Compile::flush();

        $reloaded = $this->mappedListShape($item);

        $this->assertSame($shape->id(), $reloaded->id());
        $this->assertSame($first, $reloaded(['items' => ['a', 'b']]));
        $this->assertSame($source, $reloaded->compile()->source);
        $this->assertSame('<ul><li>A</li><li>B</li></ul>', $first);
    }

    public function testCacheHitUsesTheStoredRenderer(): void
    {
        $item = Compile::shape(li(Slot::text('label')));
        $shape = $this->mappedListShape($item);
        $shape(['items' => ['a']]);

        $id = $shape->id();
        $file = $this->dir . '/' . $id . '.php';
        file_put_contents(
            $file,
            "<?php\n// purephp-shape id={$id} maps=1 v=" . Compile::CACHE_VERSION . ' php=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
            . "\nreturn static function (array \$v, array \$maps): string { return 'CACHED'; };\n"
        );

        Compile::flush();
        clearstatcache();

        $this->assertSame('CACHED', $this->mappedListShape($item)(['items' => ['a']]));
    }

    /** Keeps the map closure on one source line so both builds share an id. */
    private function mappedListShape(\Pure\Compile\Shape $item): \Pure\Compile\Shape
    {
        return Compile::shape(ul(
            Slot::each('items', $item, static fn (mixed $item): array => ['label' => strtoupper((string)$item)])
        ));
    }

    public function testCorruptedCacheIsRegenerated(): void
    {
        $shape = Compile::shape(div(span('x')));
        $shape->compile();
        $file = $this->dir . '/' . $shape->id() . '.php';

        file_put_contents($file, "<?php\n// purephp-shape id={$shape->id()} maps=0 v=" . Compile::CACHE_VERSION . ' php=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . "\nreturn 42;\n");

        $reloaded = Compile::shape(div(span('x')));
        $this->assertSame('<div><span>x</span></div>', $reloaded([]));
        $this->assertStringContainsString('return static function', (string)file_get_contents($file));
    }

    public function testGarbageCacheFileIsRegenerated(): void
    {
        $shape = Compile::shape(div(span('x')));
        $shape->compile();
        $file = $this->dir . '/' . $shape->id() . '.php';

        file_put_contents($file, 'not php at all');

        $this->assertSame('<div><span>x</span></div>', Compile::shape(div(span('x')))([]));
        $this->assertStringContainsString('<?php', (string)file_get_contents($file));
    }

    public function testMutatingATreeAfterCompileCannotPoisonTheCache(): void
    {
        $tree = div(span(Slot::text('v')))->class('c');
        $shape = Compile::shape($tree);

        $this->assertSame('<div class="c"><span>a</span></div>', $shape(['v' => 'a']));

        Compile::clearCache();
        $tree->class('late');
        Compile::flush();

        $this->assertSame('<div class="late"><span>a</span></div>', $shape(['v' => 'a']));

        $fresh = Compile::shape(div(span(Slot::text('v')))->class('c'));

        $this->assertNotSame($shape->id(), $fresh->id());
        $this->assertSame('<div class="c"><span>a</span></div>', $fresh(['v' => 'a']));
    }

    public function testIdFollowsTreeMutationWithoutPoisoningTheCache(): void
    {
        $tree = div(span(Slot::text('v')));
        $shape = Compile::shape($tree);
        $idBefore = $shape->id();

        $tree->class('late');

        $this->assertNotSame($idBefore, $shape->id());
        $this->assertSame('<div class="late"><span>a</span></div>', $shape(['v' => 'a']));

        $fresh = Compile::shape(div(span(Slot::text('v'))));

        $this->assertNotSame($shape->id(), $fresh->id());
        $this->assertSame('<div><span>a</span></div>', $fresh(['v' => 'a']));
    }

    public function testMapCountMismatchIsRegenerated(): void
    {
        $item = Compile::shape(li(Slot::text('label')));
        $build = static fn (): \Pure\Compile\Shape => Compile::shape(ul(
            Slot::each('items', $item, static fn (mixed $item): array => ['label' => (string)$item])
        ));

        $shape = $build();
        $shape(['items' => ['a']]);
        $file = $this->dir . '/' . $shape->id() . '.php';

        $contents = (string)file_get_contents($file);
        file_put_contents($file, (string)preg_replace('/maps=\d+/', 'maps=0', $contents, 1));

        $this->assertSame('<ul><li>a</li></ul>', $build()(['items' => ['a']]));
    }

    public function testClearCacheRemovesOnlyOwnFiles(): void
    {
        $shape = Compile::shape(div('x'));
        $shape->compile();
        $foreign = $this->dir . '/keep.php';
        file_put_contents($foreign, "<?php\n// not ours\n");

        $this->assertSame(1, Compile::clearCache());
        $this->assertFileDoesNotExist($this->dir . '/' . $shape->id() . '.php');
        $this->assertFileExists($foreign);

        unlink($foreign);
    }

    public function testCachePathNullDisablesDiskWrites(): void
    {
        Compile::cachePath(null);

        Compile::shape(div('x'))->compile();

        $this->assertSame([], glob($this->dir . '/*.php') ?: []);
        $this->assertSame(0, Compile::clearCache());
    }

    public function testFlushInvalidatesMemoryRenderers(): void
    {
        $shape = Compile::shape(div(Slot::text('v')));

        $first = $shape->compile();
        $this->assertSame($first, $shape->compile());

        Compile::flush();

        $second = $shape->compile();
        $this->assertNotSame($first, $second);
        $this->assertSame($first->id, $second->id);
        $this->assertSame('<div>a</div>', $shape(['v' => 'a']));
    }

    public function testCachePathRejectsUnwritableDirectory(): void
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('running as root: permission checks are bypassed');
        }

        $locked = $this->dir . '/locked';
        mkdir($locked, 0o555);

        try {
            $this->expectException(InvalidArgumentException::class);

            Compile::cachePath($locked);
        } finally {
            @chmod($locked, 0o755);
            @rmdir($locked);
        }
    }

    public function testCachePathRejectsLoosePermissions(): void
    {
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('running as root: permission checks are bypassed');
        }

        $loose = $this->dir . '/loose';
        mkdir($loose, 0o700);
        chmod($loose, 0o777);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('must not be writable by group or others');

            Compile::cachePath($loose);
        } finally {
            @chmod($loose, 0o700);
            @rmdir($loose);
        }
    }

    public function testGuardWarnsOncePerCallSite(): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            if ($errno === E_USER_WARNING) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        Compile::guard(true);

        try {
            for ($i = 0; $i < 25; $i++) {
                Compile::shape(div('guarded'));
            }
        } finally {
            Compile::guard(false);
            restore_error_handler();
        }

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('static', $warnings[0]);
        $this->assertStringContainsString(__FILE__ . ':', $warnings[0]);
        $this->assertStringNotContainsString('src/Compile/Compile.php', $warnings[0]);
    }

    public function testGuardEnablesFromTheEnvironmentVariable(): void
    {
        (new ReflectionProperty(ShapeGuard::class, 'enabled'))->setValue(null, null);
        (new ReflectionProperty(ShapeGuard::class, 'calls'))->setValue(null, []);
        putenv('PURE_COMPILE_GUARD=1');

        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            if ($errno === E_USER_WARNING) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        try {
            for ($i = 0; $i < 25; $i++) {
                Compile::shape(div('guarded'));
            }
        } finally {
            restore_error_handler();
            putenv('PURE_COMPILE_GUARD');
            Compile::guard(false);
            (new ReflectionProperty(ShapeGuard::class, 'calls'))->setValue(null, []);
        }

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('static', $warnings[0]);
    }

    public function testStaleCacheVersionIsRegenerated(): void
    {
        $shape = Compile::shape(div(span('x')));
        $shape->compile();
        $file = $this->dir . '/' . $shape->id() . '.php';
        $contents = (string)file_get_contents($file);
        file_put_contents($file, (string)preg_replace('/v=\d+/', 'v=1', $contents, 1));

        $this->assertSame('<div><span>x</span></div>', Compile::shape(div(span('x')))([]));
        $this->assertStringContainsString('v=' . Compile::CACHE_VERSION, (string)file_get_contents($file));
    }
}
