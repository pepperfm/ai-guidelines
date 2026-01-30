#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Build `resources/boost/guidelines/core.blade.php` from markdown sources.
 *
 * Why:
 * - Laravel Boost package contract expects a single entry-point file:
 *   `resources/boost/guidelines/core.blade.php`.
 * - We want to author guidelines as multiple `*.md` files.
 *
 * Usage:
 *   php scripts/build-boost-guidelines.php
 *
 * Options:
 *   --src=...     Source directory (default: resources/boost/guidelines)
 *   --out=...     Output file (default: <src>/core.blade.php)
 *   --index=...   Optional index file (default: <src>/_index.txt)
 *   --check       Exit non-zero if output would change (CI-friendly)
 *   --dry-run     Print a summary, do not write the output
 *
 * Index file format (one path per line, relative to <src>):
 *   # comments are allowed
 *   _core/core.md
 *   laravel/core.md
 *   laravel/macros.md
 *   nuxt-ui/core.md
 *   element-plus/core.md
 */

final class BuildBoostGuidelines
{
    /** @var array<int, string> */
    private array $argv;

    public function __construct(array $argv)
    {
        $this->argv = $argv;
    }

    public static function main(array $argv): int
    {
        return (new self($argv))->run();
    }

    private function run(): int
    {
        $opts = $this->parseOpts();

        $root = $this->projectRoot();
        $src = $this->realOrJoin($opts['src'] ?? null, $root . '/resources/boost/guidelines');
        $out = $this->realOrJoin($opts['out'] ?? null, $src . '/core.blade.php');
        $index = $this->realOrJoin($opts['index'] ?? null, $src . '/_index.txt');

        $check = isset($opts['check']);
        $dryRun = isset($opts['dry-run']);

        if (!is_dir($src)) {
            fwrite(STDERR, "[build-boost-guidelines] Source dir not found: {$src}\n");
            return 2;
        }

        $mdFiles = $this->resolveMarkdownFiles($src, $index);
        if ($mdFiles === []) {
            fwrite(STDERR, "[build-boost-guidelines] No markdown sources found in: {$src}\n");
            return 2;
        }

        $compiled = $this->compile($src, $mdFiles);

        // Detect changes
        $existing = is_file($out) ? file_get_contents($out) : null;
        if ($existing !== null && $this->normalizeNewlines($existing) === $compiled) {
            if (!$dryRun) {
                fwrite(STDOUT, "[build-boost-guidelines] Up to date: {$this->rel($root, $out)}\n");
            }
            return 0;
        }

        if ($check) {
            fwrite(STDERR, "[build-boost-guidelines] Output is stale: {$this->rel($root, $out)}\n");
            return 1;
        }

        if ($dryRun) {
            fwrite(STDOUT, "[build-boost-guidelines] Would write: {$this->rel($root, $out)}\n");
            fwrite(STDOUT, "[build-boost-guidelines] Sources (" . count($mdFiles) . "):\n");
            foreach ($mdFiles as $p) {
                fwrite(STDOUT, "  - {$p}\n");
            }
            return 0;
        }

        $outDir = dirname($out);
        if (!is_dir($outDir)) {
            mkdir($outDir, 0777, true);
        }

        file_put_contents($out, $compiled);
        fwrite(STDOUT, "[build-boost-guidelines] Wrote: {$this->rel($root, $out)}\n");
        return 0;
    }

    /**
     * @return array<string, bool|string>
     */
    private function parseOpts(): array
    {
        // Minimal getopt implementation supporting --key=value and flags.
        $out = [];
        foreach ($this->argv as $arg) {
            if (!str_starts_with($arg, '--')) {
                continue;
            }
            $arg = substr($arg, 2);
            if ($arg === '') {
                continue;
            }
            if (!str_contains($arg, '=')) {
                $out[$arg] = true;
                continue;
            }
            [$k, $v] = explode('=', $arg, 2);
            $out[$k] = $v;
        }
        return $out;
    }

    private function projectRoot(): string
    {
        // scripts/ -> project root
        $root = realpath(__DIR__ . '/..');
        return $root !== false ? $root : dirname(__DIR__);
    }

    private function realOrJoin(?string $maybePath, string $default): string
    {
        $path = $maybePath ?: $default;

        // Resolve relative paths against project root for convenience.
        if (!str_starts_with($path, '/') && !preg_match('~^[A-Za-z]:\\\\~', $path)) {
            $root = $this->projectRoot();
            $path = $root . '/' . ltrim($path, '/');
        }

        return $path;
    }

    /**
     * @param array<int, string> $mdFiles Relative paths (from $src) using `/`.
     */
    private function compile(string $src, array $mdFiles): string
    {
        $chunks = [];

        foreach ($mdFiles as $relPath) {
            $abs = $src . '/' . $relPath;
            if (!is_file($abs)) {
                fwrite(STDERR, "[build-boost-guidelines] Missing source: {$relPath}\n");
                continue;
            }

            $content = file_get_contents($abs);
            $content = $content === false ? '' : $this->normalizeNewlines($content);
            $content = rtrim($content);

            if (str_contains($content, '@endverbatim')) {
                fwrite(STDERR, "[build-boost-guidelines] ERROR: {$relPath} contains '@endverbatim' which breaks compilation.\n");
                exit(2);
            }

            $chunks[] = "<!-- BEGIN: {$relPath} -->\n\n{$content}\n\n<!-- END: {$relPath} -->";
        }

        $body = implode("\n\n---\n\n", $chunks);

        // Stable checksum so the generated file only changes when content changes.
        $checksum = sha1($body);

        $header = implode("\n", [
            '{{-- AUTO-GENERATED FILE. DO NOT EDIT DIRECTLY. --}}',
            '{{-- This file is generated from markdown sources in resources/boost/guidelines/**/*.md --}}',
            '{{-- Run: php scripts/build-boost-guidelines.php --}}',
            "{{-- Checksum: {$checksum} --}}",
            '',
        ]);

        // Wrap the entire markdown into a single verbatim block so Blade never tries
        // to interpret any accidental directives (e.g. lines starting with '@').
        $out = $header
            . "@verbatim\n"
            . $body
            . "\n@endverbatim\n";

        return $this->normalizeNewlines($out);
    }

    /**
     * @return array<int, string> Relative paths (from $src) using `/`.
     */
    private function resolveMarkdownFiles(string $src, string $indexFile): array
    {
        if (is_file($indexFile)) {
            return $this->readIndex($src, $indexFile);
        }

        // Default discovery: include every *.md file under the guidelines directory,
        // ordered predictably. `_core` first; within each folder: core.md first.
        $paths = [];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $name = $file->getFilename();
            if ($name === 'core.blade.php') {
                continue;
            }
            if (!str_ends_with(strtolower($name), '.md')) {
                continue;
            }
            if (strtolower($name) === 'readme.md') {
                continue;
            }

            $abs = $file->getRealPath();
            if ($abs === false) {
                continue;
            }

            $rel = ltrim(str_replace('\\', '/', substr($abs, strlen(rtrim($src, '/')))), '/');
            $paths[] = $rel;
        }

        // Group by top-level folder.
        $grouped = [];
        foreach ($paths as $rel) {
            $parts = explode('/', $rel);
            $top = $parts[0] ?? '';
            $grouped[$top][] = $rel;
        }

        // Sort folders with `_core` first.
        $folders = array_keys($grouped);
        usort($folders, static function (string $a, string $b): int {
            if ($a === $b) {
                return 0;
            }
            if ($a === '_core') {
                return -1;
            }
            if ($b === '_core') {
                return 1;
            }
            return $a <=> $b;
        });

        $ordered = [];
        foreach ($folders as $folder) {
            $files = $grouped[$folder] ?? [];

            // Sort within folder: core.md first, then alphabetical.
            usort($files, static function (string $a, string $b): int {
                $aBase = basename($a);
                $bBase = basename($b);
                if ($aBase === $bBase) {
                    return $a <=> $b;
                }
                if ($aBase === 'core.md') {
                    return -1;
                }
                if ($bBase === 'core.md') {
                    return 1;
                }
                return $a <=> $b;
            });

            foreach ($files as $f) {
                $ordered[] = $f;
            }
        }

        return $ordered;
    }

    /**
     * @return array<int, string>
     */
    private function readIndex(string $src, string $indexFile): array
    {
        $lines = file($indexFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $line = str_replace('\\', '/', $line);
            $abs = $src . '/' . $line;
            if (!is_file($abs)) {
                fwrite(STDERR, "[build-boost-guidelines] WARNING: index references missing file: {$line}\n");
                continue;
            }
            $out[] = $line;
        }
        return $out;
    }

    private function normalizeNewlines(string $s): string
    {
        // Convert CRLF/CR to LF.
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        return $s;
    }

    private function rel(string $root, string $path): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, $root)) {
            return substr($path, strlen($root));
        }
        return $path;
    }
}

exit(BuildBoostGuidelines::main(array_slice($_SERVER['argv'], 1)));
