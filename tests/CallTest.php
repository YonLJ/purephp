<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Component\Call;

use function Pure\Component\component;

use Pure\Component\Prop;
use Pure\Component\Registry;
use Pure\Component\Trusted;
use Pure\Core\DevMode;
use Pure\Core\Markup;
use Pure\Core\MissingSlotException;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\HTML\{div, h2, li, p, section, ul};

class CallTest extends TestCase
{
    private static ?string $dir = null;

    private static string $suffix = '';

    protected function setUp(): void
    {
        parent::setUp();

        Compile::cachePath(null);
        Compile::flush();
        DevMode::reset();

        self::fixtures();
    }

    protected function tearDown(): void
    {
        DevMode::reset();

        parent::tearDown();
    }

    public function testFluentCallBindsPropsAndChildren(): void
    {
        $html = FluentCard(h2('Pro'), p('Everything'))
            ->type('Free')
            ->features([['value' => '10 users'], ['value' => '2 GB']])
            ->text('Sign up')
            ->class('btn btn-lg')
            ->render();

        $this->assertSame(
            '<div class="card"><h2>Pro</h2><p>Everything</p>'
            . '<h2 class="card-title">Free</h2><p>Sign up</p>'
            . '<ul class="list"><li>10 users</li><li>2 GB</li></ul></div>',
            $html
        );
    }

    public function testCallIsMarkupAndNestsLikeATag(): void
    {
        $call = FluentCard(h2('Pro'))->type('Free')->features([])->text('Sign up');

        $this->assertInstanceOf(Call::class, $call);
        $this->assertInstanceOf(Markup::class, $call);
        $this->assertSame($call->render(), (string)$call);
        $this->assertSame(
            '<section><div class="card"><h2>Pro</h2><h2 class="card-title">Free</h2><p>Sign up</p><ul class="list"></ul></div></section>',
            section($call)->render()
        );
    }

    public function testChildlessCallRendersEmptyChildren(): void
    {
        $this->assertSame(
            '<div class="card"><h2 class="card-title">Free</h2><p>Sign up</p><ul class="list"></ul></div>',
            FluentCard()->type('Free')->features([])->text('Sign up')->render()
        );
    }

    public function testChildrenOnAComponentWithoutAChildrenSlotThrow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("component 'FluentPlain' does not read children");

        component('FluentPlain', 'child')->text('x')->render();
    }

    public function testArrayChildrenAreFlattened(): void
    {
        $this->assertSame(
            '<div class="card"><h2>Pro</h2><p>Nested</p><h2 class="card-title">Free</h2><p>Sign up</p><ul class="list"></ul></div>',
            FluentCard([h2('Pro'), [p('Nested')]])->type('Free')->features([])->text('Sign up')->render()
        );
    }

    public function testTextChildrenAreEscapedAndRawIsVerbatim(): void
    {
        $this->assertSame(
            '<div class="card">&lt;b&gt;x&lt;/b&gt;<h2 class="card-title">Free</h2><p>Sign up</p><ul class="list"></ul></div>',
            FluentCard('<b>x</b>')->type('Free')->features([])->text('Sign up')->render()
        );

        $this->assertSame(
            '<div class="card"><b>y</b><h2 class="card-title">Free</h2><p>Sign up</p><ul class="list"></ul></div>',
            FluentCard(Raw::of('<b>y</b>'))->type('Free')->features([])->text('Sign up')->render()
        );
    }

    public function testSlotChildIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('a Slot cannot be a child of a call');

        FluentCard(Slot::value('x'))->type('Free')->features([])->text('Sign up')->render();
    }

    public function testChildrenCannotBeAProp(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pass children to the call itself');

        FluentCard()->children('x');
    }

    public function testPropTakesExactlyOneValue(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("prop 'type()' takes exactly one value");

        FluentCard()->type('a', 'b');
    }

    public function testSlotPropIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("prop 'type' cannot be a Slot");

        FluentCard()->type(Slot::value('x'));
    }

    public function testPropsSetSeveralValuesAtOnce(): void
    {
        $this->assertSame(
            '<div class="card"><h2 class="card-title">Free</h2><p>Sign up</p><ul class="list"></ul></div>',
            FluentCard()->props(['type' => 'Free', 'features' => [], 'text' => 'Sign up'])->render()
        );

        $this->assertSame(
            '<div class="card"><h2 class="card-title">Free</h2><p>Sign up</p><ul class="list"></ul></div>',
            FluentCard()->props(['type' => 'Free', 'features' => [], 'text' => 'Sign up', 'unused' => null])->render()
        );
    }

    public function testClassAndStyleJoinLikeTagSetters(): void
    {
        $this->assertSame('<div class="text-muted">x</div>', FluentPlain('x')->class('text-muted')->render());
        $this->assertSame('<div class="btn active">x</div>', FluentPlain('x')->class('btn', 'active')->render());
        $this->assertSame('<div class="btn">x</div>', FluentPlain('x')->class('btn', false)->render());
        $this->assertSame('<div style="color: red;">x</div>', FluentPlain('x')->style(['color' => 'red'])->render());
        $this->assertSame('<div>x</div>', FluentPlain('x')->class('')->class(null)->render());
    }

    public function testNullPropLeavesThePropUnset(): void
    {
        $this->assertSame('<div>x</div>', FluentPlain('x')->class(null)->render());

        $this->expectException(MissingSlotException::class);
        $this->expectExceptionMessage("slot 'text' is required but was not provided");

        FluentPlain(null)->render();
    }

    public function testPrepareTransformsPropsAndEnforcesItsSignature(): void
    {
        $this->assertSame(
            '<div><h2>COLUMNS</h2><div class="row g-4"><b>a</b><b>b</b></div></div>',
            FluentSection('columns', 'row g-4', static fn (string $value): string => "<b>{$value}</b>")->render()
        );

        try {
            component('FluentSection')->section('columns')->class('row')->render();
            $this->fail('Expected a missing prop error.');
        } catch (InvalidArgumentException $error) {
            $this->assertSame("component 'FluentSection': missing prop 'item'.", $error->getMessage());
        }

        try {
            component('FluentSection')->section('columns')->class('row')->item('x')->sectoin('y')->render();
            $this->fail('Expected an unknown prop error.');
        } catch (InvalidArgumentException $error) {
            $this->assertSame(
                "component 'FluentSection': unknown prop 'sectoin' (did you mean 'section'?); "
                . "prepare() accepts 'section', 'class', 'item'.",
                $error->getMessage()
            );
        }

        try {
            component('FluentSection')->section(42)->class('row')->item(static fn (): string => 'x')->render();
            $this->fail('Expected a type error.');
        } catch (TypeError $error) {
            $this->assertStringContainsString("component 'FluentSection': ", $error->getMessage());
            $this->assertStringContainsString('must be of type string, int given', $error->getMessage());
        }
    }

    public function testUnknownPropIsReportedByTheGuard(): void
    {
        Compile::guard(true);

        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            if ($errno === E_USER_WARNING) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        try {
            $this->assertSame('<div>x</div>', FluentPlain('x')->tex('y')->render());
        } finally {
            Compile::guard(false);
            restore_error_handler();
        }

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString("unknown data key 'tex' (did you mean 'text'?)", $warnings[0]);
    }

    public function testDeprecatedPropWarnsInDevelopment(): void
    {
        Compile::guard(true);

        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            if ($errno === E_USER_WARNING) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        try {
            FluentDeclared('hi')->icon(Raw::of('<svg/>'))->style('x')->render();
            FluentDeclared('hi')->icon(Raw::of('<svg/>'))->style('x')->render();
        } finally {
            Compile::guard(false);
            restore_error_handler();
        }

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString("component 'FluentDeclared': prop 'style' is deprecated: use style()", $warnings[0]);
    }

    public function testTrustedPropWarnsForValuesThatAreNotMarkup(): void
    {
        Compile::guard(true);

        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            if ($errno === E_USER_WARNING) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        try {
            FluentDeclared('hi')->icon('<svg/>')->render();
        } finally {
            Compile::guard(false);
            restore_error_handler();
        }

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString("prop 'icon' is declared as markup (#[Trusted]) but received string", $warnings[0]);
        $this->assertStringContainsString('Raw::of()', $warnings[0]);
    }

    public function testTrustedPropAcceptsMarkupAndArraysOfIt(): void
    {
        Compile::guard(true);

        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            if ($errno === E_USER_WARNING) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        try {
            FluentDeclared('hi')->icon(Raw::of('<svg/>'))->render();
            FluentDeclared('hi')->icon([Raw::of('<a/>'), Raw::of('<b/>')])->render();
        } finally {
            Compile::guard(false);
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }

    public function testDeclarationsAreSilentWithoutTheGuard(): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
            if ($errno === E_USER_WARNING) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        try {
            FluentDeclared('hi')->icon('<svg/>')->render();
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }

    public function testCallRendersThroughTheArtifactWhenFresh(): void
    {
        $file = self::anchor('FluentArtifact.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\HTML\{div, h2};

            return Compile::shape(div(h2(Slot::value('title'))));
            PHP);

        ArtifactCompiler::writeAll($file, true);
        Compile::flush();

        $this->assertSame('<div><h2>Artifact</h2></div>', FluentArtifact('Artifact')->render());
    }

    public function testUnitFilePatternWithAOneLineCallFunction(): void
    {
        $name = 'Pattern' . self::suffix();
        $file = self::dir() . '/' . $name . '.cmp.php';

        file_put_contents($file, <<<PHP
            <?php

            declare(strict_types=1);

            use Pure\\Compile\\Compile;
            use Pure\\Component\\Call;
            use Pure\\Core\\Slot;

            use function Pure\\Component\\{component, register};
            use function Pure\\HTML\\div;

            register('{$name}', __FILE__, static fn (): \\Pure\\Compile\\Shape => Compile::shape(
                div(Slot::value('text'))
            ));

            function {$name}(string \$text): Call
            {
                return component('{$name}')->text(\$text);
            }
            PHP);

        require $file;

        if (!is_callable($name)) {
            $this->fail("the unit file must define {$name}()");
        }

        $this->assertSame('<div>one line</div>', $name('one line')->render());
    }

    /**
     * Register the units of this test class, idempotently: another test class
     * may reset the registry between two passes over this one.
     */
    private static function fixtures(): void
    {
        self::register('FluentCard', static fn (): \Pure\Compile\Shape => Compile::shape(
            div(
                Slot::raw('children'),
                h2(Slot::value('type'))->class('card-title'),
                p(Slot::value('text')),
                ul(Slot::each('features', li(Slot::value('value'))))->class('list')
            )->class('card')
        ));

        self::register('FluentPlain', static fn (): \Pure\Compile\Shape => Compile::shape(
            div(Slot::value('text'))
                ->class(Slot::value('class')->default(null))
                ->style(Slot::value('style')->default(null))
        ));

        self::register('FluentArtifact', static fn (): \Pure\Compile\Shape => Compile::shape(
            div(h2(Slot::value('title')))
        ));

        self::register(
            'FluentDeclared',
            static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('text'), div(Slot::raw('icon'))->class(Slot::value('style')->default(null)))
            ),
            static function (
                string $text,
                #[Trusted]
                mixed $icon,
                #[Prop(deprecated: 'use style()')]
                ?string $style = null,
            ): array {
                return ['text' => $text, 'icon' => $icon, 'style' => $style];
            }
        );

        self::register(
            'FluentSection',
            static fn (): \Pure\Compile\Shape => Compile::shape(
                div(h2(Slot::value('title')), div(Slot::raw('contents'))->class(Slot::value('class')))
            ),
            static function (string $section, string $class, callable $item): array {
                return [
                    'title' => strtoupper($section),
                    'contents' => array_map(
                        static fn (array $record): string => $item($record['value']),
                        [['value' => 'a'], ['value' => 'b']]
                    ),
                    'class' => $class,
                ];
            }
        );
    }

    /**
     * @param Closure(): \Pure\Compile\Shape $factory
     */
    private static function register(string $name, Closure $factory, ?Closure $prepare = null): void
    {
        $file = self::anchor($name . '.cmp.php');

        if (in_array($name, Registry::names(), true)) {
            return;
        }

        Registry::register($name, $file, $factory, prepare: $prepare);
    }

    /**
     * A real `*.cmp.php` path for a unit registered from this test class, so
     * artifact resolution works; the file itself stays empty.
     */
    private static function anchor(string $name, ?string $code = null): string
    {
        $path = self::dir() . '/' . $name;

        if ($code === null) {
            if (!is_file($path)) {
                file_put_contents($path, "<?php\n");
            }

            return $path;
        }

        if (!is_file($path) || file_get_contents($path) !== $code) {
            file_put_contents($path, $code);
        }

        return $path;
    }

    private static function dir(): string
    {
        if (self::$dir !== null) {
            return self::$dir;
        }

        $dir = sys_get_temp_dir() . '/purephp-call-' . bin2hex(random_bytes(6));
        mkdir($dir, 0o700, true);
        self::$dir = $dir;

        register_shutdown_function(static function () use ($dir): void {
            foreach (scandir($dir) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    @unlink($dir . '/' . $entry);
                }
            }

            @rmdir($dir);
        });

        return $dir;
    }

    private static function suffix(): string
    {
        return self::$suffix !== '' ? self::$suffix : self::$suffix = bin2hex(random_bytes(4));
    }
}

/**
 * The call functions a unit file defines next to register(), declared here
 * because the units of this test class are registered from the test itself.
 */
function FluentCard(mixed ...$children): Call
{
    return component('FluentCard', ...$children);
}

function FluentPlain(?string $text): Call
{
    return component('FluentPlain')->text($text);
}

function FluentArtifact(string $title): Call
{
    return component('FluentArtifact')->title($title);
}

function FluentSection(string $section, string $class, callable $item): Call
{
    return component('FluentSection')->section($section)->class($class)->item($item);
}

function FluentDeclared(string $text): Call
{
    return component('FluentDeclared')->text($text);
}
