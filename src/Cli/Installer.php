<?php
declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Installer
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly bool $force = false,
        private readonly bool $dryRun = false,
    ) {}

    public function install(Config $config): InstallResult
    {
        $result = new InstallResult();

        $targetBase = Paths::normalize($this->projectRoot . DIRECTORY_SEPARATOR . $config->target);
        $packageBase = Paths::packageBase();
        $resourceBase = $packageBase . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'guidelines';

        // Ensure base dir exists
        $this->ensureDir($targetBase, $result);

        foreach ($config->presets as $presetId) {
            $src = $resourceBase . DIRECTORY_SEPARATOR . $presetId . DIRECTORY_SEPARATOR . 'core.md';
            $dstDir = $targetBase . DIRECTORY_SEPARATOR . $presetId;
            $dst = $dstDir . DIRECTORY_SEPARATOR . 'core.md';

            if (!is_file($src)) {
                $result->addError("Preset '{$presetId}': source not found: {$src}");
                continue;
            }

            $this->ensureDir($dstDir, $result);

            $this->linkOrCopy(
                config: $config,
                src: $src,
                dst: $dst,
                result: $result
            );
        }

        return $result;
    }

    private function ensureDir(string $dir, InstallResult $result): void
    {
        if (is_dir($dir)) {
            return;
        }

        if ($this->dryRun) {
            $result->addAction("[dry-run] mkdir -p {$dir}");
            return;
        }

        if (@mkdir($dir, 0777, true) === false && !is_dir($dir)) {
            $result->addError("Failed to create directory: {$dir}");
        } else {
            $result->addAction("mkdir -p {$dir}");
        }
    }

    private function linkOrCopy(Config $config, string $src, string $dst, InstallResult $result): void
    {
        // If exists and points to same content/target, skip
        if (file_exists($dst) || is_link($dst)) {
            if (!$this->force && $this->isAlreadyCorrect($config, $src, $dst)) {
                $result->addSkipped($dst . ' (already up to date)');
                return;
            }

            if (!$this->force) {
                $result->addSkipped($dst . ' (exists, use --force to overwrite)');
                return;
            }

            $this->remove($dst, $result);
        }

        if ($config->isSymlink()) {
            $this->createSymlink($src, $dst, $result);
            if (is_link($dst)) {
                return;
            }

            // If symlink failed, fallback to copy
            $result->addWarning("Symlink failed for {$dst}. Falling back to copy.");
        }

        $this->copyFile($src, $dst, $result);
    }

    private function createSymlink(string $src, string $dst, InstallResult $result): void
    {
        $dstDir = dirname($dst);
        $relative = Paths::relative($dstDir, $src);

        if ($this->dryRun) {
            $result->addAction("[dry-run] symlink {$relative} -> {$dst}");
            return;
        }

        // Suppress warnings; we'll detect success via is_link
        @symlink($relative, $dst);

        if (is_link($dst)) {
            $result->addAction("symlink {$relative} -> {$dst}");
            return;
        }

        // Attempt absolute symlink as a second try
        @symlink($src, $dst);
        if (is_link($dst)) {
            $result->addAction("symlink {$src} -> {$dst}");
            return;
        }

        $result->addWarning("Unable to create symlink for {$dst} (src: {$src})");
    }

    private function copyFile(string $src, string $dst, InstallResult $result): void
    {
        if ($this->dryRun) {
            $result->addAction("[dry-run] copy {$src} -> {$dst}");
            return;
        }

        if (@copy($src, $dst) === false) {
            $result->addError("Failed to copy {$src} -> {$dst}");
            return;
        }

        $result->addAction("copy {$src} -> {$dst}");
    }

    private function remove(string $path, InstallResult $result): void
    {
        if ($this->dryRun) {
            $result->addAction("[dry-run] rm {$path}");
            return;
        }

        if (is_link($path) || is_file($path)) {
            if (@unlink($path) === false) {
                $result->addError("Failed to remove file: {$path}");
            } else {
                $result->addAction("rm {$path}");
            }
            return;
        }

        $result->addError("Cannot remove '{$path}' (not a file/symlink).");
    }

    private function isAlreadyCorrect(Config $config, string $src, string $dst): bool
    {
        if ($config->isSymlink() && is_link($dst)) {
            $link = readlink($dst);
            if ($link === false) {
                return false;
            }

            // Resolve link target relative to dst dir (if relative)
            $dstDir = dirname($dst);
            $resolved = $link;

            if (!str_starts_with($link, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:\\\\/?/', $link)) {
                $resolved = Paths::normalize($dstDir . DIRECTORY_SEPARATOR . $link);
            }

            // Compare normalized paths (best-effort)
            return Paths::normalize($resolved) === Paths::normalize($src);
        }

        // If copy mode, compare hashes
        if (is_file($dst) && is_file($src)) {
            return sha1_file($dst) === sha1_file($src);
        }

        return false;
    }
}

final class InstallResult
{
    /** @var array<int, string> */
    public array $actions = [];
    /** @var array<int, string> */
    public array $warnings = [];
    /** @var array<int, string> */
    public array $errors = [];
    /** @var array<int, string> */
    public array $skipped = [];

    public function ok(): bool
    {
        return $this->errors === [];
    }

    public function addAction(string $message): void
    {
        $this->actions[] = $message;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function addSkipped(string $message): void
    {
        $this->skipped[] = $message;
    }
}
