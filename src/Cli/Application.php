<?php

declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

final class Application
{
    public static function run(array $argv): int
    {
        $args = array_values($argv);
        array_shift($args);

        $command = $args[0] ?? 'init';
        if (str_starts_with($command, '-')) {
            $command = 'init';
        } else {
            array_shift($args);
        }

        $opts = self::parseOptions($args);

        if (($opts['help'] ?? false) === true || in_array($command, ['help', '-h', '--help'], true)) {
            self::printHelp();
            return 0;
        }

        if (in_array($command, ['-V', '--version', 'version'], true)) {
            self::printVersion();
            return 0;
        }

        return match ($command) {
            'list' => self::cmdList(),
            'sync' => self::cmdSync($opts),
            'init' => self::cmdInit($opts),
            default => self::unknownCommand($command),
        };
    }

    private static function cmdList(): int
    {
        $rows = [];
        foreach (Presets::all() as $id => $label) {
            $rows[] = [$id, $label, Presets::flatFileName($id)];
        }

        table(headers: ['Preset', 'Description', 'Flat filename'], rows: $rows);

        return 0;
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function cmdInit(array $opts): int
    {
        $noInteraction = (bool) ($opts['no_interaction'] ?? false);

        $projectRoot = Paths::projectRoot(getcwd() ?: '.');

        $configPath = self::resolveDefaultConfigPath(
            projectRoot: $projectRoot,
            explicit: isset($opts['config']) ? (string) $opts['config'] : null,
        );

        if ($noInteraction) {
            return self::cmdSync($opts + ['config' => $configPath, 'write_config' => false]);
        }

        intro('PepperFM: установка AI‑гайдлайнов в .ai/guidelines');

        $presets = multiselect(
            label: 'Какие пресеты подключить?',
            options: Presets::all(),
            default: ['laravel'],
            required: 'Нужно выбрать хотя бы один пресет.',
            hint: 'Можно выбрать несколько пунктов.',
        );

        /** @var array<int, string> $presets */
        $presets = Presets::filterValid($presets);

        $laravelMacrosDefault = self::boolOpt($opts, 'laravel_macros') ?? false;
        $laravelMacros = in_array('laravel', $presets, true) && confirm(
                label: 'Публиковать файл laravel/macros.md?',
                default: $laravelMacrosDefault,
            );

        note('Boost v2.0 подключает custom guidelines только из .ai/guidelines/* (без подпапок), поэтому раскладываем плоско и по номерам.');

        $layout = Config::DEFAULT_LAYOUT;

        $mode = select(
            label: 'Режим установки',
            options: [
                'symlink' => 'symlink (удобно, обновляется вместе с пакетом)',
                'copy' => 'copy (надёжно, без symlink)',
            ],
            default: (string) ($opts['mode'] ?? 'symlink'),
        );

        // Boost contract: фиксированные пути в проекте.
        $target = Config::DEFAULT_GUIDELINES_TARGET;

        $skillsDefault = self::boolOpt($opts, 'skills') ?? true;
        $skills = confirm(
            label: 'Публиковать skills в .ai/skills?',
            default: $skillsDefault,
        );

        $skillsTarget = Config::DEFAULT_SKILLS_TARGET;

        $writeConfig = confirm(
            label: "Сохранить конфиг в $configPath?",
        );

        $config = new Config(
            mode: (string) $mode,
            layout: (string) $layout,
            target: $target,
            presets: $presets,
            laravelMacros: $laravelMacros,
            skills: $skills,
            skillsTarget: $skillsTarget,
        );

        if ($writeConfig) {
            if (!self::writeConfig($configPath, $config, (bool) ($opts['dry_run'] ?? false))) {
                error("Не удалось записать конфиг: $configPath");
                return 1;
            }
            info("Конфиг сохранён: $configPath");
        }

        return self::doSync($projectRoot, $configPath, $config, $opts);
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function cmdSync(array $opts): int
    {
        $projectRoot = Paths::projectRoot(getcwd() ?: '.');

        $configPath = self::resolveDefaultConfigPath(
            projectRoot: $projectRoot,
            explicit: isset($opts['config']) ? (string) $opts['config'] : null,
        );

        $noInteraction = (bool) ($opts['no_interaction'] ?? false);
        $writeConfig = (bool) ($opts['write_config'] ?? false);

        $config = null;

        if (is_file($configPath)) {
            $config = self::readConfig($configPath);
            if ($config === null) {
                error("Конфиг повреждён или не читается: $configPath");
                return 1;
            }
        }

        $presetsFromFlags = self::parsePresets($opts);
        // Boost contract: пути и раскладка фиксированы.
        // Флаги --layout, --target, --skills-target принимаются для совместимости, но игнорируются.
        $layout = null;
        $target = null;
        $skillsTarget = null;

        $mode = isset($opts['mode']) ? (string) $opts['mode'] : null;
        $laravelMacros = self::boolOpt($opts, 'laravel_macros');
        $skills = self::boolOpt($opts, 'skills');

        if ($config === null) {
            if ($presetsFromFlags === [] && !$noInteraction) {
                intro('PepperFM: sync без конфига — выбери пресеты');
                $presetsFromFlags = multiselect(
                    label: 'Какие пресеты подключить?',
                    options: Presets::all(),
                    required: 'Нужно выбрать хотя бы один пресет.',
                );
            }

            $presetsFromFlags = Presets::filterValid($presetsFromFlags);

            if ($presetsFromFlags === []) {
                error('Не указаны пресеты. Используй --presets=laravel,element-plus или запусти init.');
                return 1;
            }

            $config = new Config(
                mode: $mode ?? 'symlink',
                layout: Config::DEFAULT_LAYOUT,
                target: Config::DEFAULT_GUIDELINES_TARGET,
                presets: $presetsFromFlags,
                laravelMacros: $laravelMacros ?? false,
                skills: $skills ?? true,
                skillsTarget: Config::DEFAULT_SKILLS_TARGET,
            );

            if ($writeConfig && self::writeConfig($configPath, $config, (bool) ($opts['dry_run'] ?? false))) {
                info("Конфиг сохранён: $configPath");
            }
        } else {
            if ($presetsFromFlags !== []) {
                $config->presets = Presets::filterValid($presetsFromFlags);
            }
            if ($mode !== null) {
                $config->mode = $mode;
            }
            if ($laravelMacros !== null) {
                $config->laravelMacros = $laravelMacros;
            }
            if ($skills !== null) {
                $config->skills = $skills;
            }

            if ($writeConfig && self::writeConfig($configPath, $config, (bool) ($opts['dry_run'] ?? false))) {
                info("Конфиг обновлён: $configPath");
            }
        }

        return self::doSync($projectRoot, $configPath, $config, $opts);
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function doSync(string $projectRoot, string $configPath, Config $config, array $opts): int
    {
        $force = (bool) ($opts['force'] ?? false);
        $dryRun = (bool) ($opts['dry_run'] ?? false);

        note('Выбранные пресеты: ' . implode(', ', $config->presets));
        note('Mode: ' . $config->mode . ' | Presets: ' . implode(', ', $config->presets) . ' | Skills: ' . ($config->skills ? 'on' : 'off') . ' | Paths: .ai/guidelines + .ai/skills (Boost v2.0 contract)');

        $installer = new Installer(
            projectRoot: $projectRoot,
            force: $force,
            dryRun: $dryRun,
        );

        $result = $installer->install($config);

        foreach ($result->actions as $m) {
            info($m);
        }
        foreach ($result->skipped as $m) {
            note($m);
        }
        foreach ($result->warnings as $m) {
            warning($m);
        }
        foreach ($result->errors as $m) {
            error($m);
        }

        if (!$result->ok()) {
            outro('Готово, но есть ошибки. Исправь и запусти sync ещё раз.');
            return 1;
        }

        $artisan = $projectRoot . DIRECTORY_SEPARATOR . 'artisan';
        $boostUpdate = (bool) ($opts['boost_update'] ?? false);

        if (!$dryRun && is_file($artisan)) {
            if ($boostUpdate) {
                info('Запускаю: php artisan boost:update');
                $exit = self::runArtisanBoostUpdate($projectRoot);
                if ($exit !== 0) {
                    warning("boost:update завершился с кодом $exit. Проверь вывод выше.");
                }
            } else {
                warning('Не забудьте запустить php artisan boost:update');
            }
        }

        outro('Готово.');
        return 0;
    }

    private static function runArtisanBoostUpdate(string $projectRoot): int
    {
        $cwd = getcwd();
        @chdir($projectRoot);

        $cmd = PHP_BINARY . ' artisan boost:update';
        $exitCode = 0;

        // passthru preserves output formatting.
        passthru($cmd, $exitCode);

        if ($cwd !== false) {
            @chdir($cwd);
        }

        return (int) $exitCode;
    }

    private static function unknownCommand(string $command): int
    {
        error("Неизвестная команда: $command");
        self::printHelp();

        return 1;
    }

    private static function printVersion(): void
    {
        info('pepperfm/ai-guidelines (CLI)');
    }

    private static function printHelp(): void
    {
        $text = <<<TXT
pfm-guidelines / pepper-guidelines — установка личных AI‑гайдлайнов в .ai/guidelines

Usage:
  pfm-guidelines                 (alias для init)
  pfm-guidelines init            интерактивно выбрать пресеты и установить
  pfm-guidelines sync            применить конфиг/параметры (создать symlink/copy)
  pfm-guidelines list            показать доступные пресеты
  pfm-guidelines help            показать эту справку

Options (init/sync):
  --presets=laravel,nuxt-ui,element-plus   список пресетов (через запятую)
  --preset=laravel                         можно повторять несколько раз
  --mode=symlink|copy
  --laravel-macros
  --skills[=true|false]
  --config=.pfm-guidelines.json
  --write-config
  --force
  --dry-run
  --no-interaction
  --boost-update

Deprecated (ignored; Boost v2.0 contract uses fixed paths):
  --layout=flat-numbered|folders
  --target=.ai/guidelines
  --skills-target=.ai/skills
Examples:
  php vendor/bin/pfm-guidelines init
  php vendor/bin/pfm-guidelines sync
  php vendor/bin/pfm-guidelines sync --no-interaction --mode=copy --presets=laravel,element-plus --write-config

TXT;

        alert($text);
    }

    /**
     * @param array<int, string> $args
     * @return array<string, mixed>
     */
    private static function parseOptions(array $args): array
    {
        $opts = ['preset' => []];

        foreach ($args as $i => $iValue) {
            $arg = $iValue;

            if (!str_starts_with($arg, '-')) {
                continue;
            }

            if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
                [$k, $v] = explode('=', substr($arg, 2), 2);
                self::pushOpt($opts, $k, $v);
                continue;
            }

            if (str_starts_with($arg, '--')) {
                $k = substr($arg, 2);

                if (in_array($k, ['force', 'dry-run', 'no-interaction', 'help', 'write-config', 'boost-update', 'laravel-macros'], true)) {
                    $opts[str_replace('-', '_', $k)] = true;
                    continue;
                }

                $v = $args[$i + 1] ?? null;
                if ($v !== null && !str_starts_with($v, '-')) {
                    $i++;
                    self::pushOpt($opts, $k, $v);
                    continue;
                }

                $opts[str_replace('-', '_', $k)] = true;
                continue;
            }

            if ($arg === '-h') {
                $opts['help'] = true;
            }
            if ($arg === '-V') {
                $opts['version'] = true;
            }
        }

        return $opts;
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function boolOpt(array $opts, string $key): ?bool
    {
        if (!array_key_exists($key, $opts)) {
            return null;
        }

        $value = $opts[$key];
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }
        if (is_string($value)) {
            $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filtered !== null) {
                return $filtered;
            }
        }

        return (bool) $value;
    }

    /**
     * @param array<string, mixed> $opts
     */
    private static function pushOpt(array &$opts, string $key, string $value): void
    {
        $keyNorm = str_replace('-', '_', $key);

        if ($keyNorm === 'preset') {
            $opts['preset'][] = $value;
            return;
        }

        $opts[$keyNorm] = $value;
    }

    /**
     * @param array<string, mixed> $opts
     * @return array<int, string>
     */
    private static function parsePresets(array $opts): array
    {
        $presets = [];

        if (isset($opts['presets'])) {
            $presets = array_merge($presets, array_filter(array_map('trim', explode(',', (string) $opts['presets']))));
        }

        if (isset($opts['preset']) && is_array($opts['preset'])) {
            foreach ($opts['preset'] as $p) {
                $presets[] = (string) $p;
            }
        }

        return Presets::filterValid($presets);
    }

    private static function resolveDefaultConfigPath(string $projectRoot, ?string $explicit): string
    {
        if ($explicit !== null && $explicit !== '') {
            return self::configPath($projectRoot, $explicit);
        }

        $pfm = Paths::normalize($projectRoot . DIRECTORY_SEPARATOR . '.pfm-guidelines.json');
        if (is_file($pfm)) {
            return $pfm;
        }

        $legacy = Paths::normalize($projectRoot . DIRECTORY_SEPARATOR . '.pepper-guidelines.json');
        if (is_file($legacy)) {
            return $legacy;
        }

        return $pfm;
    }

    private static function configPath(string $projectRoot, string $configRel): string
    {
        if (
            str_starts_with($configRel, DIRECTORY_SEPARATOR)
            || preg_match('~^[A-Za-z]:[\\\\/]~', $configRel) === 1
        ) {
            return $configRel;
        }

        return Paths::normalize($projectRoot . DIRECTORY_SEPARATOR . $configRel);
    }

    private static function readConfig(string $path): ?Config
    {
        $raw = @file_get_contents($path);
        if (!is_string($raw)) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        return Config::fromArray($data);
    }

    private static function writeConfig(string $path, Config $config, bool $dryRun): bool
    {
        $json = json_encode($config->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return false;
        }
        $json .= "\n";

        if ($dryRun) {
            info("[dry-run] write config: $path");
            return true;
        }

        return @file_put_contents($path, $json) !== false;
    }
}
