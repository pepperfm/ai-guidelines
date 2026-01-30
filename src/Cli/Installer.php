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

        $coreSrc = $resourceBase . DIRECTORY_SEPARATOR . '_core' . DIRECTORY_SEPARATOR . 'core.md';

        if (is_file($coreSrc)) {
            $coreDst = $config->isFlat()
                ? $targetBase . DIRECTORY_SEPARATOR . '01-core.md'
                : $targetBase . DIRECTORY_SEPARATOR . '_core' . DIRECTORY_SEPARATOR . 'core.md';

            $this->ensureDir(dirname($coreDst), $result);
            $this->linkOrCopy($config, $coreSrc, $coreDst, $result);
        }

        foreach ($config->presets as $presetId) {
            $src = $resourceBase . DIRECTORY_SEPARATOR . $presetId . DIRECTORY_SEPARATOR . 'core.md';

            if (!is_file($src)) {
                $result->addError("Preset '$presetId': source not found: $src");
                continue;
            }

            if ($config->isFlat()) {
                $dst = $targetBase . DIRECTORY_SEPARATOR . Presets::flatFileName($presetId);
                $this->linkOrCopy($config, $src, $dst, $result);
                if ($presetId === 'laravel') {
                    $this->installLaravelMacros($config, $resourceBase, $targetBase, null, $result);
                }
                continue;
            }

            $dstDir = $targetBase . DIRECTORY_SEPARATOR . $presetId;
            $dst = $dstDir . DIRECTORY_SEPARATOR . 'core.md';

            $this->ensureDir($dstDir, $result);
            $this->linkOrCopy($config, $src, $dst, $result);
            if ($presetId === 'laravel') {
                $this->installLaravelMacros($config, $resourceBase, $targetBase, $dstDir, $result);
            }
        }

        if ($config->skills) {
            $this->installSkills($config, $packageBase, $result);
        }

        return $result;
    }

    private function installLaravelMacros(
        Config $config,
        string $resourceBase,
        string $targetBase,
        ?string $dstDir,
        InstallResult $result
    ): void {
        if (!$config->laravelMacros) {
            return;
        }

        $macrosSrc = $resourceBase . DIRECTORY_SEPARATOR . 'laravel' . DIRECTORY_SEPARATOR . 'macros.md';
        if (!is_file($macrosSrc)) {
            $result->addError("Preset 'laravel' macros: source not found: $macrosSrc");
            return;
        }

        $macrosDst = $config->isFlat()
            ? $targetBase . DIRECTORY_SEPARATOR . Presets::laravelMacrosFlatFileName()
            : (($dstDir ?? $targetBase . DIRECTORY_SEPARATOR . 'laravel') . DIRECTORY_SEPARATOR . 'macros.md');

        $this->ensureDir(dirname($macrosDst), $result);
        $this->linkOrCopy($config, $macrosSrc, $macrosDst, $result);
    }


    private function installSkills(Config $config, string $packageBase, InstallResult $result): void
    {
        $skillsResourceBase = $packageBase . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'skills';
        if (!is_dir($skillsResourceBase)) {
            $result->addWarning("Skills resource dir not found: $skillsResourceBase");
            return;
        }

        $skillsTargetBase = Paths::normalize($this->projectRoot . DIRECTORY_SEPARATOR . $config->skillsTarget);
        $this->ensureDir($skillsTargetBase, $result);

        // Optional: index/readme for humans.
        $readmeSrc = $skillsResourceBase . DIRECTORY_SEPARATOR . 'README.md';
        if (is_file($readmeSrc)) {
            $readmeDst = $skillsTargetBase . DIRECTORY_SEPARATOR . 'README.md';
            $this->ensureDir(dirname($readmeDst), $result);
            $this->linkOrCopy($config, $readmeSrc, $readmeDst, $result);
        }

        $skillNames = Skills::forConfig($config);

        foreach ($skillNames as $skillName) {
            $srcDir = $skillsResourceBase . DIRECTORY_SEPARATOR . $skillName;
            if (!is_dir($srcDir)) {
                $result->addWarning("Skill '$skillName' not found: $srcDir");
                continue;
            }

            $dstDir = $skillsTargetBase . DIRECTORY_SEPARATOR . $skillName;
            $this->installTree($config, $srcDir, $dstDir, $result);
        }
    }

    private function installTree(Config $config, string $srcDir, string $dstDir, InstallResult $result): void
    {
        $this->ensureDir($dstDir, $result);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $srcPath = $item->getPathname();
            $rel = substr($srcPath, strlen($srcDir) + 1);
            $dstPath = $dstDir . DIRECTORY_SEPARATOR . $rel;

            if ($item->isDir()) {
                $this->ensureDir($dstPath, $result);
                continue;
            }

            $this->ensureDir(dirname($dstPath), $result);
            $this->linkOrCopy($config, $srcPath, $dstPath, $result);
        }
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
