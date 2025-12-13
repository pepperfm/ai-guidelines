<?php

declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final readonly class Installer
{
    public function __construct(
        private string $projectRoot,
        private bool $force = false,
        private bool $dryRun = false,
    ) {
    }

    public function install(Config $config): InstallResult
    {
        $result = new InstallResult();

        $targetBase = Paths::normalize($this->projectRoot . DIRECTORY_SEPARATOR . $config->target);
        $packageBase = Paths::packageBase();
        $resourceBase = $packageBase . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'guidelines';

        $this->ensureDir($targetBase, $result);

        foreach ($config->presets as $presetId) {
            $src = $resourceBase . DIRECTORY_SEPARATOR . $presetId . DIRECTORY_SEPARATOR . 'core.md';

            if (!is_file($src)) {
                $result->addError("Preset '$presetId': source not found: $src");
                continue;
            }

            if ($config->isFlat()) {
                $dst = $targetBase . DIRECTORY_SEPARATOR . Presets::flatFileName($presetId);
                $this->linkOrCopy($config, $src, $dst, $result);
                continue;
            }

            $dstDir = $targetBase . DIRECTORY_SEPARATOR . $presetId;
            $dst = $dstDir . DIRECTORY_SEPARATOR . 'core.md';

            $this->ensureDir($dstDir, $result);
            $this->linkOrCopy($config, $src, $dst, $result);
        }

        return $result;
    }

    private function ensureDir(string $dir, InstallResult $result): void
    {
        if (is_dir($dir)) {
            return;
        }

        if ($this->dryRun) {
            $result->addAction("[dry-run] mkdir -p $dir");
            return;
        }

        if (@mkdir($dir, 0777, true) === false && !is_dir($dir)) {
            $result->addError("Failed to create directory: $dir");
            return;
        }

        $result->addAction("mkdir -p $dir");
    }

    private function linkOrCopy(Config $config, string $src, string $dst, InstallResult $result): void
    {
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

            $result->addWarning("Symlink failed for $dst. Falling back to copy.");
        }

        $this->copyFile($src, $dst, $result);
    }

    private function createSymlink(string $src, string $dst, InstallResult $result): void
    {
        $dstDir = dirname($dst);
        $relative = Paths::relative($dstDir, $src);

        if ($this->dryRun) {
            $result->addAction("[dry-run] symlink $relative -> $dst");
            return;
        }

        @symlink($relative, $dst);

        if (is_link($dst)) {
            $result->addAction("symlink $relative -> $dst");
            return;
        }

        @symlink($src, $dst);

        if (is_link($dst)) {
            $result->addAction("symlink $src -> $dst");
            return;
        }

        $result->addWarning("Unable to create symlink for $dst (src: $src)");
    }

    private function copyFile(string $src, string $dst, InstallResult $result): void
    {
        if ($this->dryRun) {
            $result->addAction("[dry-run] copy $src -> $dst");
            return;
        }

        if (@copy($src, $dst) === false) {
            $result->addError("Failed to copy $src -> $dst");
            return;
        }

        $result->addAction("copy $src -> $dst");
    }

    private function remove(string $path, InstallResult $result): void
    {
        if ($this->dryRun) {
            $result->addAction("[dry-run] rm $path");
            return;
        }

        if (is_link($path) || is_file($path)) {
            if (@unlink($path) === false) {
                $result->addError("Failed to remove file: $path");
                return;
            }

            $result->addAction("rm $path");
            return;
        }

        $result->addError("Cannot remove '$path' (not a file/symlink).");
    }

    private function isAlreadyCorrect(Config $config, string $src, string $dst): bool
    {
        if ($config->isSymlink() && is_link($dst)) {
            $link = readlink($dst);
            if ($link === false) {
                return false;
            }

            $dstDir = dirname($dst);
            $resolved = $link;

            if (!str_starts_with($link, DIRECTORY_SEPARATOR) && !preg_match('~^[A-Za-z]:[\\\\/]~', $link)) {
                $resolved = Paths::normalize($dstDir . DIRECTORY_SEPARATOR . $link);
            }

            return Paths::normalize($resolved) === Paths::normalize($src);
        }

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
