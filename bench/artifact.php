<?php

declare(strict_types=1);

/**
 * Precompiled artifacts: write phase, then require (cold and warm) plus render
 * versus the runtime compile path, on the features page of examples/bootstrap.
 * Run each
 * phase in its own process so opcache can cache the artifact file:
 *
 *   php bench/artifact.php --write   # compile and write the artifact
 *   php bench/artifact.php           # require the artifact and render
 */

require __DIR__ . '/../vendor/autoload.php';

use Pure\Compile\Internal\ArtifactCompiler;

$root = dirname(__DIR__);
$dir = sys_get_temp_dir() . '/purephp-artifact-bench';
$shapeFile = $dir . '/page.shape.php';
$artifactFile = $dir . '/page.pure.php';

if (!is_dir($dir)) {
    mkdir($dir, 0o700, true);
}

if (in_array('--write', $argv, true)) {
    $unitFile = var_export($root . '/examples/bootstrap/views/features.cmp.php', true);
    file_put_contents(
        $shapeFile,
        "<?php\n\nrequire_once {$unitFile};\n\n"
        . "return (\\Pure\\Component\\Registry::unitsFor({$unitFile})['Features']['factory'])();\n"
    );

    $start = hrtime(true);
    ArtifactCompiler::write($shapeFile);

    printf("wrote %s in %.0f us\n", $artifactFile, (hrtime(true) - $start) / 1000);

    exit(0);
}

if (!is_file($artifactFile)) {
    fwrite(STDERR, "run 'php bench/artifact.php --write' first\n");

    exit(1);
}

// opcache does not cache a file whose mtime falls inside its revalidate window,
// so age the freshly written artifact to measure the steady-state warm require.
touch($artifactFile, time() - 5);

$iterations = (int)($argv[1] ?? 3000);
require $root . '/examples/bootstrap/app/bootstrap.php';
require $root . '/examples/bootstrap/app/controllers/FeaturesController.php';
require_once $root . '/examples/bootstrap/views/features.cmp.php';
$bindings = featuresBindings();

$start = hrtime(true);
$renderer = require $artifactFile;
$cold = (hrtime(true) - $start) / 1000;

// A second require re-executes the op_array: with opcache this is what every
// request of a worker pays, without it the file is parsed again each time.
$start = hrtime(true);
require $artifactFile;
$warm = (hrtime(true) - $start) / 1000;

$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $rendered = $renderer->render($bindings);
}
$artifactRender = (hrtime(true) - $start) / 1000 / $iterations;

$start = hrtime(true);
$unitFile = $root . '/examples/bootstrap/views/features.cmp.php';
require_once $unitFile;
$shape = \Pure\Compile\Compile::toShape((\Pure\Component\Registry::unitsFor($unitFile)['Features']['factory'])());
if ($shape === null) {
    throw new \RuntimeException('the Features factory must return a tag tree or a Pure\\Compile\\Shape.');
}
$shape->compile();
$compile = (hrtime(true) - $start) / 1000;

$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $expected = $shape($bindings);
}
$shapeRender = (hrtime(true) - $start) / 1000 / $iterations;

printf("artifact: require %.0f us cold / %.0f us warm | render %.1f us/render\n", $cold, $warm, $artifactRender);
printf("shape:    build tree + compile %.0f us | render %.1f us/render\n", $compile, $shapeRender);
printf("output identical: %s | id match: %s | %d bytes\n", $rendered === $expected ? 'yes' : 'NO', $renderer->id === $shape->id() ? 'yes' : 'NO', strlen($rendered));
