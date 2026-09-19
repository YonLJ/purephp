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

    public function testFluentUnitChecksPrepareAgainstTheSlots(): void
    {
        $file = $this->unitFile('fluent.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{div, h2};

            register('CheckFluent', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(h2(Slot::value('title')), div(Slot::raw('contents')))
                ),
                prepare: static function (string $section, string $class): array {
                    return ['title' => strtoupper($section), 'contents' => $class];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('0 error(s), 0 warning(s).', $result['stdout']);
    }

    public function testFluentUnitPrepareMismatchIsReported(): void
    {
        $file = $this->unitFile('fluent-typo.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{div, h2};

            register('CheckFluentTypo', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(h2(Slot::value('title')))
                ),
                prepare: static function (string $section): array {
                    return ['titel' => strtoupper($section)];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "prepare() returns 'titel' but the template does not read it (did you mean 'title'?)",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "required slot 'title' is not returned by prepare()",
            $result['stdout']
        );
    }

    public function testFluentUnitWithAComputedPrepareResultIsNotCompared(): void
    {
        $file = $this->unitFile('fluent-computed.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckFluentComputed', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'))
                ),
                prepare: static function (string $title): array {
                    $data = ['title' => $title];

                    return $data;
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('prepare() does not return one array literal', $result['stdout']);
    }

    public function testDeclaredSlotsCarryTheBindingsOfAComputedPrepare(): void
    {
        $file = $this->unitFile('fluent-declared.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{div, h2};

            register('CheckDeclared', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(h2(Slot::value('title')), div(Slot::raw('contents')))
                ),
                prepare: static function (
                    #[Prop(slot: 'title')] string $text,
                    #[Prop(slot: 'contents')] string $body,
                ): array {
                    $data = ['title' => strtoupper($text), 'contents' => $body];

                    return $data;
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('its bindings are read from the #[Prop] declarations', $result['stdout']);
        $this->assertStringContainsString('0 error(s), 0 warning(s).', $result['stdout']);
    }

    public function testUndeclaredRequiredSlotIsAnErrorWhenPrepareIsComputed(): void
    {
        $file = $this->unitFile('fluent-undeclared.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{div, h2};

            register('CheckUndeclared', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(h2(Slot::value('title')), div(Slot::raw('contents')))
                ),
                prepare: static function (
                    #[Prop(slot: 'title')] string $text,
                    string $body,
                ): array {
                    $data = ['title' => strtoupper($text), 'contents' => $body];

                    return $data;
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "required slot 'contents' is not covered by any declaration and prepare() does not return a readable array literal",
            $result['stdout']
        );
    }

    public function testDeclaredSlotMustBeReturnedByAPrepareLiteral(): void
    {
        $file = $this->unitFile('fluent-declared-literal.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{div, h2};

            register('CheckDeclaredLiteral', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(h2(Slot::value('title')))
                ),
                prepare: static function (#[Prop(slot: 'title')] string $text): array {
                    return ['titel' => $text];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "prop \$text declares slot 'title', which prepare() does not return",
            $result['stdout']
        );
    }

    public function testDeclaredSlotMustBeReadByTheTemplate(): void
    {
        $file = $this->unitFile('fluent-declared-typo.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{div, h2};

            register('CheckDeclaredTypo', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(h2(Slot::value('title')))
                ),
                prepare: static function (#[Prop(slot: 'titel')] string $text): array {
                    return ['titel' => $text];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "prop \$text declares slot 'titel', which the template does not read (did you mean 'title'?)",
            $result['stdout']
        );
    }

    public function testTwoPropsDeclaringOneSlotAreAnError(): void
    {
        $file = $this->unitFile('fluent-declared-duplicate.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckDeclaredDuplicate', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'))
                ),
                prepare: static function (
                    #[Prop(slot: 'title')] string $text,
                    #[Prop(slot: 'title')] string $heading,
                ): array {
                    return ['title' => $text . $heading];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "props \$text and \$heading declare the same slot 'title'",
            $result['stdout']
        );
    }

    public function testDeclaredRequiredMustMatchTheSignature(): void
    {
        $file = $this->unitFile('fluent-declared-required.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckDeclaredRequired', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'), Slot::value('class')->default(''))
                ),
                prepare: static function (
                    #[Prop(required: false)] string $title,
                    #[Prop(required: true)] ?string $class = null,
                ): array {
                    return ['title' => $title, 'class' => $class];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            'prop $title is declared optional but its parameter has no default value; callers must pass it',
            $result['stdout']
        );
        $this->assertStringContainsString(
            'prop $class is declared required but its parameter has a default value; callers may omit it',
            $result['stdout']
        );
    }

    public function testDeclaredItemIsCheckedAgainstTheItemShape(): void
    {
        $file = $this->unitFile('fluent-declared-item.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{li, ul};

            register('CheckDeclaredItem', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    ul(Slot::each('features', li(Slot::value('value'))))
                ),
                prepare: static function (#[Prop(item: 'value')] array $features): array {
                    return [
                        'features' => array_map(static fn (string $feature): array => ['value' => $feature], $features),
                    ];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('0 error(s), 0 warning(s).', $result['stdout']);
    }

    public function testDeclaredItemMismatchesAreReported(): void
    {
        $this->unitFile('item-wrong.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{li, ul};

            register('CheckItemWrong', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    ul(Slot::each('features', li(Slot::value('value'))))
                ),
                prepare: static function (#[Prop(item: 'vaule')] array $features): array {
                    return ['features' => $features];
                }
            );
            PHP);

        $this->unitFile('item-multi.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{li, ul};

            register('CheckItemMulti', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    ul(Slot::each('features', li(Slot::value('value'), Slot::value('url'))))
                ),
                prepare: static function (#[Prop(item: 'value')] array $features): array {
                    return ['features' => $features];
                }
            );
            PHP);

        $this->unitFile('item-text.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckItemText', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'))
                ),
                prepare: static function (#[Prop(item: 'value')] string $title): array {
                    return ['title' => $title];
                }
            );
            PHP);

        $this->unitFile('item-static.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{li, ul};

            register('CheckItemStatic', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    ul(Slot::each('features', li('static')))
                ),
                prepare: static function (#[Prop(item: 'value')] array $features): array {
                    return ['features' => $features];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "prop \$features declares one item slot 'vaule' but the item shape of slot 'features' reads 'value' (did you mean 'value'?)",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "prop \$features declares one item slot 'value' but the item shape of slot 'features' reads 'value', 'url'",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "prop \$title declares item: 'value' but slot 'title' is not a list slot",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "prop \$features declares item: 'value' but the item shape of slot 'features' reads no slots",
            $result['stdout']
        );
    }

    public function testDeprecatedPropIsReportedAtCallSites(): void
    {
        $this->unitFile('deprecated-target.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckDeprecated', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'))->style(Slot::value('style'))
                ),
                prepare: static function (
                    #[Prop(slot: 'title')] string $title,
                    #[Prop(deprecated: 'use class()')] string $style = '',
                ): array {
                    return ['title' => $title, 'style' => $style];
                }
            );
            PHP);

        $this->unitFile('deprecated-calls.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;

            use function Pure\Component\register;

            register('CheckDeprecatedPage', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                \Pure\HTML\div()
            ));

            function checkDeprecatedBody(): string
            {
                return (string) CheckDeprecated('Home')->style('color: red');
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString(
            "component 'CheckDeprecated': the call binds 'style', which is deprecated: use class()",
            $result['stdout']
        );
        $this->assertStringContainsString('0 error(s), 1 warning(s).', $result['stdout']);
    }

    public function testTrustedPropMustBindARawSlot(): void
    {
        $this->unitFile('trusted-text.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Trusted;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckTrustedText', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'))
                ),
                prepare: static function (#[Trusted] string $title): array {
                    return ['title' => $title];
                }
            );
            PHP);

        $this->unitFile('trusted-raw.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Trusted;
            use Pure\Core\Markup;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckTrustedRaw', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::raw('icon'))
                ),
                prepare: static function (#[Trusted] Markup $icon): array {
                    return ['icon' => $icon];
                }
            );
            PHP);

        $this->unitFile('trusted-mixed.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Trusted;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckTrustedMixed', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::raw('body'), Slot::value('body'))
                ),
                prepare: static function (#[Trusted] mixed $body): array {
                    return ['body' => $body];
                }
            );
            PHP);

        $this->unitFile('trusted-unread.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Trusted;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckTrustedUnread', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div()
                ),
                prepare: static function (#[Trusted] mixed $extra): array {
                    return ['extra' => $extra];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString("ok: component 'CheckTrustedRaw'", $result['stdout']);
        $this->assertStringContainsString(
            "prop \$title is declared as markup (#[Trusted]) but slot 'title' is a text slot",
            $result['stdout']
        );
        $this->assertStringContainsString(
            'prop $title is declared as markup (#[Trusted]) but typed string; type it Markup|Stringable (or mixed) to accept markup, or drop the attribute and wrap the value in Raw::of() at the call site',
            $result['stdout']
        );
        $this->assertStringContainsString(
            "prop \$body is declared as markup (#[Trusted]) but slot 'body' is also read as a text slot",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "prop \$extra is declared as markup (#[Trusted]) but slot 'extra' is not read by the template",
            $result['stdout']
        );
    }

    public function testBindsDeclaresTheKeysOfAComputedPrepare(): void
    {
        $this->unitFile('binds-covered.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Binds;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckBindsCovered', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'), div(Slot::raw('desc')))
                ),
                prepare: #[Binds('title', 'desc')] static function (): array {
                    $data = ['title' => 'Pricing', 'desc' => 'Plans'];

                    return $data;
                }
            );
            PHP);

        $this->unitFile('binds-uncovered.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Binds;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckBindsUncovered', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'), div(Slot::raw('desc')))
                ),
                prepare: #[Binds('title')] static function (): array {
                    $data = ['title' => 'Pricing'];

                    return $data;
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            'its bindings are read from the #[Binds] declarations',
            $result['stdout']
        );
        $this->assertStringContainsString(
            "required slot 'desc' is not covered by any declaration and prepare() does not return a readable array literal",
            $result['stdout']
        );
    }

    public function testBindsMustMatchAReadableLiteral(): void
    {
        $file = $this->unitFile('binds-literal.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Binds;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckBindsLiteral', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'), div(Slot::raw('desc')))
                ),
                prepare: #[Binds('title', 'titel')] static function (): array {
                    return ['title' => 'Pricing', 'desc' => 'Plans'];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString("#[Binds] declares 'titel', which prepare() does not return", $result['stdout']);
    }

    public function testBindsOnABindingsHelperIsRead(): void
    {
        $file = $this->unitFile('binds-helper.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Binds;
            use Pure\Core\Slot;

            use function Pure\Component\{register, render};
            use function Pure\HTML\div;

            register('CheckBindsPage', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'), Slot::value('desc'))
            ));

            #[Binds('title', 'desc')]
            function checkBindsPageBindings(): array
            {
                $data = ['title' => 'Pricing', 'desc' => 'Plans'];

                return $data;
            }

            function checkBindsPage(): string
            {
                return render('CheckBindsPage', ...checkBindsPageBindings());
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringNotContainsString('slots are not compared', $result['stdout']);
        $this->assertStringContainsString('0 error(s), 0 warning(s).', $result['stdout']);
    }

    public function testCallSiteItemKeysAreCheckedAgainstTheItemShape(): void
    {
        $this->unitFile('items-target.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{a, li, ul};

            register('CheckItems', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                ul(Slot::each('links', li(a(Slot::value('text'))->href(Slot::value('href')))))
            ));
            PHP);

        $this->unitFile('items-declared.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Prop;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\{a, li, ul};

            register('CheckItemsDeclared', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    ul(Slot::each('links', li(a(Slot::value('text'))->href(Slot::value('href')))))
                ),
                prepare: static function (#[Prop(slot: 'links')] array $rows): array {
                    return ['links' => $rows];
                }
            );
            PHP);

        $this->unitFile('items-calls.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;

            use function Pure\Component\register;

            register('CheckItemsPage', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                \Pure\HTML\div()
            ));

            function checkItemsBody(): string
            {
                return (string) CheckItems('x')->links([['text' => 'Team', 'href' => '#']])
                    . (string) CheckItems('x')->links([['text' => 'A', 'href' => '#'], ['txet' => 'B', 'href' => '#']])
                    . (string) CheckItems('x')->links([['text' => 'Team']])
                    . (string) CheckItemsDeclared('x')->rows([['text' => 'Team', 'href' => '#']])
                    . (string) CheckItems('x')->links(['a', 'b']);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "item 2 of 'links' binds 'txet', which the item shape of slot 'links' does not read (did you mean 'text'?)",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "item 2 of 'links' does not provide 'text', which the item shape of slot 'links' requires",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "item 1 of 'links' does not provide 'href', which the item shape of slot 'links' requires",
            $result['stdout']
        );
        $this->assertStringNotContainsString("of 'rows'", $result['stdout']);
        $this->assertStringNotContainsString("binds 'a'", $result['stdout']);
        $this->assertStringContainsString('3 error(s)', $result['stdout']);
    }

    public function testFluentCallSitePropsAreCheckedAgainstTheTarget(): void
    {
        $this->unitFile('target.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckTarget', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));
            PHP);

        $page = $this->unitFile('page-calls.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\section;

            register('CheckPageCalls', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                section(Slot::raw('body'))
            ));

            function checkPageCallsBody(): string
            {
                return (string) CheckTarget('child')->titel('x');
            }

            function checkPageCallsDynamic(array $props): string
            {
                return (string) CheckTarget('child')->titel(...$props);
            }

            function checkPageCallsChildren(): string
            {
                return (string) CheckTarget('child')->children('x');
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(1, $result['code']);
        $this->assertStringContainsString(
            "component 'CheckTarget': the call binds 'titel', which the target does not accept (did you mean 'title'?)",
            $result['stdout']
        );
        $this->assertStringContainsString(
            "component 'CheckTarget': pass children to the call itself, e.g. CheckTarget(\$children)",
            $result['stdout']
        );
        $this->assertStringNotContainsString('titel(...$props)', $result['stdout']);
    }

    public function testPrepareKeysAreReadFromAnInterpolatedLiteral(): void
    {
        $file = $this->unitFile('fluent-interpolated.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\div;

            register('CheckFluentInterpolated', __FILE__,
                factory: static fn (): \Pure\Compile\Shape => Compile::shape(
                    div(Slot::value('title'))->style(Slot::value('style')->default(null))
                ),
                prepare: static function (string $title, string $bg): array {
                    return [
                        'title' => $title,
                        'style' => "background-image: url('{$bg}');",
                    ];
                }
            );
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringNotContainsString('prepare() does not return one array literal', $result['stdout']);
        $this->assertStringContainsString('0 error(s), 0 warning(s).', $result['stdout']);
    }

    public function testFluentUnitWithoutPreparePointsAtTheCallSites(): void
    {
        $file = $this->unitFile('fluent-plain.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Call;
            use Pure\Core\Slot;

            use function Pure\Component\{component, register};
            use function Pure\HTML\div;

            register('CheckFluentPlain', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function CheckFluentPlain(mixed ...$children): Call
            {
                return component('CheckFluentPlain', ...$children);
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $file]);

        $this->assertSame(0, $result['code']);
        $this->assertStringContainsString('fluent unit: its props are the template slots', $result['stdout']);
    }

    public function testCallMethodsAreNotReportedAsUnknownProps(): void
    {
        $this->unitFile('target-methods.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Component\Call;
            use Pure\Core\Slot;

            use function Pure\Component\{component, register};
            use function Pure\HTML\div;

            register('CheckTargetMethods', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                div(Slot::value('title'))
            ));

            function CheckTargetMethods(mixed ...$children): Call
            {
                return component('CheckTargetMethods', ...$children);
            }
            PHP);

        $page = $this->unitFile('page-methods.cmp.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            use Pure\Compile\Compile;
            use Pure\Core\Slot;

            use function Pure\Component\register;
            use function Pure\HTML\section;

            register('CheckPageMethods', __FILE__, static fn (): \Pure\Compile\Shape => Compile::shape(
                section(Slot::raw('body'))
            ));

            function checkPageMethodsBody(): string
            {
                return (string) CheckTargetMethods()->props(['title' => 'a'])->render();
            }
            PHP);

        $result = $this->runCheck(['pure', 'check', $this->dir]);

        $this->assertSame(0, $result['code']);
        $this->assertStringNotContainsString("binds 'props'", $result['stdout']);
        $this->assertStringNotContainsString("binds 'render'", $result['stdout']);
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
