<?php
declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Paths
{
    /*
     * Return absolute path to package base directory (the directory that contains composer.json).
     */
    public static function packageBase(): string
    {
        // src/Cli/Paths.php -> src/Cli -> src -> package root
        return dirname(__DIR__, 2);
    }

    /*
     * Try to find project root by walking up from current working directory until composer.json is found.
     * If not found, returns the original working directory.
     */
    public static function projectRoot(string $startDir): string
    {
        $dir = self::normalize($startDir);

        while (true) {
            if (is_file($dir . DIRECTORY_SEPARATOR . 'composer.json')) {
                return $dir;
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                return self::normalize($startDir);
            }
            $dir = $parent;
        }
    }

    public static function normalize(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        // Remove trailing separators (except root)
        if (strlen($path) > 1) {
            $path = rtrim($path, DIRECTORY_SEPARATOR);
        }
        return $path;
    }

    /*
     * Make a relative path from $fromDir (directory) to $toPath (file or directory).
     * If paths are on different roots (e.g. different Windows drives), returns $toPath as-is.
     */
    public static function relative(string $fromDir, string $toPath): string
    {
        $fromDir = self::normalize($fromDir);
        $toPath = self::normalize($toPath);

        [$fromRoot, $fromRest] = self::splitRoot($fromDir);
        [$toRoot, $toRest] = self::splitRoot($toPath);

        if ($fromRoot !== $toRoot) {
            return $toPath; // can't make relative
        }

        $fromParts = self::splitParts($fromRest);
        $toParts = self::splitParts($toRest);

        // Remove common prefix
        while ($fromParts && $toParts && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $up = array_fill(0, count($fromParts), '..');
        $relParts = array_merge($up, $toParts);

        if ($relParts === []) {
            return '.';
        }

        return implode(DIRECTORY_SEPARATOR, $relParts);
    }

    /**
     * @return array{0: string, 1: string} [root, rest]
     */
    private static function splitRoot(string $path): array
    {
        // Windows drive root like C:\
        if (preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1) {
            $root = substr($path, 0, 2) . DIRECTORY_SEPARATOR;
            $rest = substr($path, 2);
            $rest = ltrim($rest, DIRECTORY_SEPARATOR);
            return [$root, $rest];
        }

        // Unix root
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return [DIRECTORY_SEPARATOR, ltrim($path, DIRECTORY_SEPARATOR)];
        }

        // Relative paths: treat root as empty
        return ['', $path];
    }

    /**
     * @return array<int, string>
     */
    private static function splitParts(string $path): array
    {
        $path = trim($path, DIRECTORY_SEPARATOR);
        if ($path === '') {
            return [];
        }
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        // filter empty
        $out = [];
        foreach ($parts as $p) {
            if ($p !== '' && $p !== '.') {
                $out[] = $p;
            }
        }
        return $out;
    }
}
