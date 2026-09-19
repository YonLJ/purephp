<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use InvalidArgumentException;
use ParseError;
use Pure\Compile\Compile;
use Pure\Compile\Renderer;
use Pure\Compile\Shape;
use Pure\Core\Tag;
use Throwable;

/**
 * Builds precompiled artifacts: plain PHP files that return a Renderer, and
 * plain views that need no library at render time.
 *
 * An artifact carries the compiled closure, the shape fingerprint and the
 * document header of the root tag, so loading it needs neither the shape tree
 * nor the compile cache. A plain view carries the document header and the
 * markup itself, and reads its slots from locals, so production can serve it
 * without purephp installed.
 *
 * @internal
 */
final class ArtifactCompiler
{
    public const SUFFIX = '.shape.php';

    /** @var list<string> The unit suffixes `pure compile` and the binder understand. */
    public const UNIT_SUFFIXES = ['.shape.php', '.cmp.php'];

    /**
     * The artifact contents for one shape file.
     *
     * @param string $shapeFile The path of a `*.shape.php` file returning a Shape.
     * @param bool $plain Build the plain view instead of the Renderer artifact.
     * @return string The generated source.
     */
    public static function build(string $shapeFile, bool $plain = false): string
    {
        $sources = self::buildAll($shapeFile, $plain);

        return $plain ? (string)$sources['plain'] : $sources['artifact'];
    }

    /**
     * The generated sources of one shape file.
     *
     * The shape file is loaded once, so both flavors describe the same tree.
     *
     * @param string $shapeFile The path of a `*.shape.php` file returning a Shape.
     * @param bool $plain Also build the plain view.
     * @return array{artifact: string, plain: ?string}
     */
    public static function buildAll(string $shapeFile, bool $plain = false): array
    {
        return self::buildUnit($shapeFile, self::load($shapeFile), $plain);
    }

    /**
     * The generated sources of one unit file whose shape is already known.
     *
     * `pure compile` uses this for `*.cmp.php` units, where loading the file
     * registers the lazy factory and the shape comes from the registry.
     *
     * @param string $unitFile The unit file path (any unit suffix).
     * @param Shape $shape The template to compile.
     * @param bool $plain Also build the plain view.
     * @return array{artifact: string, plain: ?string}
     */
    public static function buildUnit(string $unitFile, Shape $shape, bool $plain = false): array
    {
        $project = self::project($unitFile, $shape, $plain);

        return ['artifact' => $project['artifact'], 'plain' => $project['plain']];
    }

    /**
     * Compile a shape file and write its artifact next to it.
     *
     * The artifact is written atomically and loaded once before the rename, so
     * an artifact on disk always loads as the renderer it was built from.
     *
     * @param string $shapeFile The path of a `*.shape.php` file returning a Shape.
     * @param bool $plain Also write the plain view next to the artifact.
     * @return string The artifact path.
     */
    public static function write(string $shapeFile, bool $plain = false): string
    {
        return self::writeAll($shapeFile, $plain)['artifact'];
    }

    /**
     * Write the artifact (and plain view) of a shape file, skipping the files
     * whose content is already current.
     *
     * The shape is still loaded and compiled, so a change in anything the shape
     * file pulls in is picked up; only the write, the load-back verification
     * and the rename are skipped for unchanged files, which is most of the cost
     * of compiling an unchanged tree.
     *
     * @param string $shapeFile The path of a `*.shape.php` file returning a Shape.
     * @param bool $plain Also write the plain view next to the artifact.
     * @return array{artifact: string, plain: ?string, artifactWritten: bool, plainWritten: bool}
     */
    public static function writeChanged(string $shapeFile, bool $plain = false): array
    {
        return self::writeUnit($shapeFile, self::load($shapeFile), $plain);
    }

    /**
     * Write the artifact (and plain view) of one unit file whose shape is
     * already known, skipping the files whose content is already current.
     *
     * @param string $unitFile The unit file path (any unit suffix).
     * @param Shape $shape The template to compile.
     * @param bool $plain Also write the plain view next to the artifact.
     * @return array{artifact: string, plain: ?string, artifactWritten: bool, plainWritten: bool}
     */
    public static function writeUnit(string $unitFile, Shape $shape, bool $plain = false): array
    {
        $project = self::project($unitFile, $shape, $plain);
        $artifact = self::artifactPath($unitFile);
        $plainFile = $plain ? self::plainPath($unitFile) : null;

        $artifactWritten = self::writeIfChanged($artifact, $project['artifact'], $unitFile, $project['id'], 'artifact');
        $plainWritten = false;

        if ($plainFile !== null && $project['plain'] !== null) {
            $plainWritten = self::writeIfChanged($plainFile, $project['plain'], $unitFile, $project['id'], 'plain view');
        }

        return [
            'artifact' => $artifact,
            'plain' => $plainFile,
            'artifactWritten' => $artifactWritten,
            'plainWritten' => $plainWritten,
        ];
    }

    /**
     * @param string $shapeFile The shape file path.
     * @param bool $plain Also write the plain view.
     * @return array{artifact: string, plain: ?string} The written paths.
     */
    public static function writeAll(string $shapeFile, bool $plain = false): array
    {
        $project = self::project($shapeFile, self::load($shapeFile), $plain);
        $artifact = self::artifactPath($shapeFile);
        $plainFile = $plain ? self::plainPath($shapeFile) : null;

        self::writeFile($artifact, $project['artifact'], $shapeFile, $project['id'], 'artifact');

        if ($plainFile !== null && $project['plain'] !== null) {
            self::writeFile($plainFile, $project['plain'], $shapeFile, $project['id'], 'plain view');
        }

        return ['artifact' => $artifact, 'plain' => $plainFile];
    }

    /**
     * The artifact path of a shape file: `foo.shape.php` -> `foo.pure.php`.
     *
     * @param string $shapeFile The shape file path.
     * @return string The artifact path.
     */
    public static function artifactPath(string $shapeFile): string
    {
        return self::sibling($shapeFile, '.pure.php');
    }

    /**
     * The plain view path of a shape file: `foo.shape.php` -> `foo.plain.php`.
     *
     * @param string $shapeFile The shape file path.
     * @return string The plain view path.
     */
    public static function plainPath(string $shapeFile): string
    {
        return self::sibling($shapeFile, '.plain.php');
    }

    private static function sibling(string $unitFile, string $suffix): string
    {
        foreach (self::UNIT_SUFFIXES as $unitSuffix) {
            if (str_ends_with($unitFile, $unitSuffix)) {
                return substr($unitFile, 0, -strlen($unitSuffix)) . $suffix;
            }
        }

        throw new InvalidArgumentException(
            "'{$unitFile}' is not a " . implode(' or ', array_map(static fn (string $s): string => '*' . $s, self::UNIT_SUFFIXES)) . ' file.'
        );
    }

    /**
     * @param string $unitFile The unit file path.
     * @param Shape $shape The template to compile.
     * @param bool $plain Also build the plain view.
     * @return array{artifact: string, plain: ?string, id: string}
     */
    private static function project(string $unitFile, Shape $shape, bool $plain): array
    {
        $tree = $shape->tree();
        $id = ShapeIndex::of($tree)->id();

        return [
            'artifact' => self::artifactFile($unitFile, $tree, $id),
            'plain' => $plain ? self::plainFile($unitFile, $tree, $id) : null,
            'id' => $id,
        ];
    }

    /**
     * The artifact source: a namespace block that returns the compiled renderer.
     *
     * @param string $shapeFile The shape file path.
     * @param Tag $tree The shape tree.
     * @param string $id The structure fingerprint.
     * @return string The artifact source.
     */
    private static function artifactFile(string $shapeFile, Tag $tree, string $id): string
    {
        $source = TemplateGenerator::source($tree);
        $header = $tree->documentHeader();

        $artifact = "<?php\n";
        $artifact .= "/**\n * Compiled from " . basename($shapeFile) . ", do not edit.\n"
            . " * Run `pure compile` to rebuild after changing the shape.\n */\n";
        $artifact .= "// purephp-shape id={$id}"
            . ' v=' . Compile::CACHE_VERSION . ' php=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
            . ' header=' . ($header === '' ? '-' : base64_encode($header)) . "\n\n";
        $artifact .= "declare(strict_types=1);\n\n";
        $artifact .= "use Pure\\Compile\\Renderer;\n";

        foreach (TemplateGenerator::imports($source) as $import) {
            $artifact .= $import . "\n";
        }

        // One line, static message: the guard is paid per artifact require and
        // its cost is dominated by parsing, so a concatenated version and a
        // multi-line message make every cold page load measurably slower.
        $artifact .= "\nif (" . Compile::CACHE_VERSION . " !== \\Pure\\Compile\\Compile::CACHE_VERSION) { throw new \\RuntimeException("
            . "'stale purephp artifact: generated for cache version " . Compile::CACHE_VERSION
            . "; run `pure compile` to rebuild'); }\n\n";
        $artifact .= "\$pureBody = " . $source . ";\n\n";
        $artifact .= "return new Renderer(\n";
        $artifact .= "    \$pureBody,\n";
        $artifact .= "    '',\n";
        $artifact .= '    ' . var_export($id, true) . ",\n";
        $artifact .= '    ' . var_export($header, true) . "\n";
        $artifact .= ");\n";

        return $artifact;
    }

    /**
     * The plain view source: markup, native PHP and the document header.
     *
     * @param string $shapeFile The shape file path.
     * @param Tag $tree The shape tree.
     * @param string $id The structure fingerprint.
     * @return string The plain view source.
     */
    private static function plainFile(string $shapeFile, Tag $tree, string $id): string
    {
        $file = "<?php\n";
        $file .= "/**\n * Compiled from " . basename($shapeFile) . ", do not edit.\n"
            . " * Plain view: render it with the view data extracted into locals; no library\n"
            . " * is needed at load time. Root slots become the local variables of the view\n"
            . " * and carry `@var` annotations derived from the shape, so static analyzers\n"
            . " * can follow them without an exclusion.\n"
            . " * Run `pure compile --plain` to rebuild after changing the shape.\n */\n";
        $file .= "// purephp-shape id={$id}"
            . ' v=' . Compile::CACHE_VERSION . ' php=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . "\n\n";
        $file .= "declare(strict_types=1);\n\n";

        $annotation = ScopeTypes::docblock($tree);

        if ($annotation !== '') {
            $file .= $annotation . "\n";
        }

        return $file . PlainGenerator::view($tree);
    }

    /**
     * Write one generated file when its content changed.
     *
     * @return bool Whether the file was written.
     */
    private static function writeIfChanged(string $path, string $contents, string $shapeFile, string $id, string $kind): bool
    {
        if (is_file($path) && @file_get_contents($path) === $contents) {
            return false;
        }

        self::writeFile($path, $contents, $shapeFile, $id, $kind);

        return true;
    }

    /**
     * Write one generated file atomically, after verifying what it is.
     */
    private static function writeFile(string $path, string $contents, string $shapeFile, string $id, string $kind): void
    {
        $temporary = @tempnam(dirname($path), 'pure-artifact-');

        if ($temporary === false) {
            throw new InvalidArgumentException("could not create a temporary file next to '{$path}'.");
        }

        try {
            if (@file_put_contents($temporary, $contents) !== strlen($contents)) {
                throw new InvalidArgumentException("could not write the {$kind} for '{$shapeFile}'.");
            }

            if ($kind === 'artifact') {
                self::verify($temporary, $shapeFile, $id);
            } else {
                self::verifyPlain($temporary, $shapeFile, $id);
            }

            @chmod($temporary, 0o644);

            if (!@rename($temporary, $path)) {
                throw new InvalidArgumentException("could not replace '{$path}'.");
            }
        } catch (Throwable $error) {
            @unlink($temporary);

            throw $error;
        }
    }

    private static function load(string $shapeFile): Shape
    {
        if (!is_file($shapeFile)) {
            throw new InvalidArgumentException("'{$shapeFile}' does not exist.");
        }

        $level = ob_get_level();
        ob_start();

        try {
            $shape = (static fn (string $file): mixed => require $file)($shapeFile);
        } catch (Throwable $error) {
            self::discardOutput($level);

            throw new InvalidArgumentException("'{$shapeFile}' could not be loaded: " . $error->getMessage(), 0, $error);
        }

        self::discardOutput($level);

        if (!$shape instanceof Shape) {
            throw new InvalidArgumentException("'{$shapeFile}' must return a Pure\\Compile\\Shape, got " . get_debug_type($shape) . '.');
        }

        return $shape;
    }

    /**
     * Drop everything echoed while the shape file loaded: building artifacts is
     * not rendering, and shape files may pull in markup (sprite maps, partials)
     * that the request-time path prints.
     */
    private static function discardOutput(int $level): void
    {
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
    }

    /**
     * Load the written artifact and check that it returns the compiled renderer.
     */
    private static function verify(string $temporary, string $shapeFile, string $id): void
    {
        try {
            $renderer = (static fn (string $file): mixed => require $file)($temporary);
        } catch (Throwable $error) {
            throw new InvalidArgumentException("the artifact for '{$shapeFile}' failed to load: " . $error->getMessage(), 0, $error);
        }

        if (!$renderer instanceof Renderer || $renderer->id !== $id) {
            throw new InvalidArgumentException("the artifact for '{$shapeFile}' did not load as its compiled renderer.");
        }
    }

    /**
     * A plain view is markup, so it is verified without executing it: it must
     * carry the built fingerprint and parse.
     */
    private static function verifyPlain(string $temporary, string $shapeFile, string $id): void
    {
        $source = (string)file_get_contents($temporary);

        if (!str_contains($source, '// purephp-shape id=' . $id . ' ')) {
            throw new InvalidArgumentException("the plain view for '{$shapeFile}' did not keep its fingerprint.");
        }

        try {
            $tokens = token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $error) {
            throw new InvalidArgumentException("the plain view for '{$shapeFile}' is not valid PHP: " . $error->getMessage(), 0, $error);
        }

        if ($tokens === []) {
            throw new InvalidArgumentException("the plain view for '{$shapeFile}' is empty.");
        }
    }
}
