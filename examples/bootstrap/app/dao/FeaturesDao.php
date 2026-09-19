<?php declare(strict_types=1);

/**
 * The data access of the features page: the records as they arrive from the
 * data source.
 *
 * The page content lives in app/data/features.json and is read here — a
 * stand-in for the I/O a component does not want to know about: a database
 * query, an API call or a CMS import. The decoded document is cached for the
 * process, the way a repository would cache one fetch per request, so callers
 * may ask for it as often as they like.
 *
 * @return array<string, list<array<string, string>>>
 */
final class FeaturesDao
{
    /**
     * @return array<string, list<array<string, string>>>
     */
    public static function content(): array
    {
        static $content;

        if ($content !== null) {
            return $content;
        }

        $file = __DIR__ . '/../data/features.json';
        $json = @file_get_contents($file);

        if ($json === false) {
            throw new RuntimeException("could not read the page content from '{$file}'.");
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException("'{$file}' must hold a JSON object.");
        }

        /** @var array<string, list<array<string, string>>> $content */
        $content = $decoded;

        return $content;
    }
}
