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
        array_shift($args); // script name

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
            'list' => self::cmdList($opts),
            'sync' => self::cmdSync($opts),
            'init' => self::cmdInit($opts),
            default => self::unknownCommand($command),
        };
    }

    /** @param array<string, mixed> $opts */
    private static function cmdList(array $opts): int
    {
        $rows = [];
        foreach (Presets::all() as $id => $label) {
            $rows[] = [$id, $label];
        }

        // If prompts fallback is not supported, table() still prints a readable output.
        table(headers: ['Preset', 'Description'], rows: $rows);

        return 0;
    }

    /** @param array<string, mixed> $opts */
    private static function cmdInit(array $opts): int
    {
        $noInteraction = (bool) ($opts['no_interaction'] ?? false);

        $projectRoot = Paths::projectRoot(getcwd() ?: '.');
        $configPath = self::configPath($projectRoot, (string) ($opts['config'] ?? '.pfm-guidelines.json'));

        if ($noInteraction) {
            // In no-interaction mode, init behaves like sync with explicit flags.
            return self::cmdSync($opts + ['config' => $configPath, 'write_config' => true]);
        }

        intro('PepperFM: установка AI‑гайдлайнов в .ai/guidelines');

        $presetOptions = Presets::all();

        $presets = multiselect(
            label: 'Какие пресеты подключить?',
            options: $presetOptions,
            default: array_values(array_intersect(['laravel', 'element-plus', 'nuxt-ui'], array_keys($presetOptions))),
            required: 'Нужно выбрать хотя бы один пресет.',
            hint: 'Можно выбрать несколько пунктов.',
        );

        /** @var array<int, string> $presets */
        $presets = Presets::filterValid($presets);

        $mode = select(
            label: 'Режим установки',
            options: [
                'symlink' => 'symlink (удобно, обновляется вместе с пакетом)',
                'copy' => 'copy (надёжно, без symlink)',
            ],
            default: (string) ($opts['mode'] ?? 'symlink'),
            hint: 'Если symlink не поддерживается, мы автоматически откатимся на copy.',
        );

        $target = text(
            label: 'Куда положить гайдлайны внутри проекта?',
            default: (string) ($opts['target'] ?? '.ai/guidelines/pepperfm'),
            required: true,
            hint: 'Boost читает .ai/guidelines/** и использует их при сборке AGENTS.md.',
        );

        $writeConfig = confirm(
            label: "Сохранить конфиг в {$configPath}?",
            default: true,
        );

        $config = new Config(
            mode: (string) $mode,
            target: (string) $target,
            presets: $presets,
        );

        if ($writeConfig) {
            if (!self::writeConfig($configPath, $config, (bool) ($opts['dry_run'] ?? false))) {
                error("Не удалось записать конфиг: {$configPath}");
                return 1;
            }
            info("Конфиг сохранён: {$configPath}");
        } else {
            note('Конфиг не сохранён — можно запускать sync с параметрами.');
        }

        return self::doSync($projectRoot, $configPath, $config, $opts);
    }

    /** @param array<string, mixed> $opts */
    private static function cmdSync(array $opts): int
    {
        $projectRoot = Paths::projectRoot(getcwd() ?: '.');
        $configRel = (string) ($opts['config'] ?? '.pfm-guidelines.json');
        $configPath = self::configPath($projectRoot, $configRel);

        $noInteraction = (bool) ($opts['no_interaction'] ?? false);
        $writeConfig = (bool) ($opts['write_config'] ?? false);

        // 1) Try config first
        $config = null;
        if (is_file($configPath)) {
            $config = self::readConfig($configPath);
            if ($config === null) {
                error("Конфиг повреждён или не читается: {$configPath}");
                return 1;
            }
        }

        // 2) Override config from CLI flags
        $presetsFromFlags = self::parsePresets($opts);
        $mode = isset($opts['mode']) ? (string) $opts['mode'] : null;
        $target = isset($opts['target']) ? (string) $opts['target'] : null;

        if ($config === null) {
            // No config yet; require flags or interactive
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
                target: $target ?? '.ai/guidelines/pepperfm',
                presets: $presetsFromFlags,
            );

            if ($writeConfig && self::writeConfig($configPath, $config, (bool) ($opts['dry_run'] ?? false))) {
                info("Конфиг сохранён: {$configPath}");
            }
        } else {
            // Apply overrides to existing config
            if ($presetsFromFlags !== []) {
                $config->presets = Presets::filterValid($presetsFromFlags);
            }
            if ($mode !== null) {
                $config->mode = $mode;
            }
            if ($target !== null) {
                $config->target = $target;
            }

            if ($writeConfig && self::writeConfig($configPath, $config, (bool) ($opts['dry_run'] ?? false))) {
                info("Конфиг обновлён: {$configPath}");
            }
        }

        return self::doSync($projectRoot, $configPath, $config, $opts);
    }

    /** @param array<string, mixed> $opts */
    private static function doSync(string $projectRoot, string $configPath, Config $config, array $opts): int
    {
        $force = (bool) ($opts['force'] ?? false);
        $dryRun = (bool) ($opts['dry_run'] ?? false);

        note('Выбранные пресеты: ' . implode(', ', $config->presets));
        note('Режим: ' . $config->mode . ' | target: ' . $config->target);

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

        // Offer to run boost:update if artisan exists (interactive only)
        $noInteraction = (bool) ($opts['no_interaction'] ?? false);
        $runBoost = (bool) ($opts['boost_update'] ?? false);

        $artisan = $projectRoot . DIRECTORY_SEPARATOR . 'artisan';
        if (is_file($artisan) && !$dryRun) {
            if ($runBoost) {
                self::runBoostUpdate($projectRoot);
            } elseif (!$noInteraction) {
                $do = confirm(
                    label: 'Запустить php artisan boost:update сейчас?',
                    default: true,
                    hint: 'Чтобы Boost пересобрал AGENTS.md из .ai/guidelines/*',
                );
                if ($do) {
                    self::runBoostUpdate($projectRoot);
                }
            }
        }

        outro('Готово.');
        return 0;
    }

    private static function runBoostUpdate(string $projectRoot): void
    {
        info('Запускаю: php artisan boost:update');
        $cmd = 'php artisan boost:update';

        $cwd = getcwd();
        chdir($projectRoot);
        passthru($cmd, $code);
        if ($cwd !== false) {
            chdir($cwd);
        }

        if ($code !== 0) {
            warning("boost:update завершился с кодом {$code}");
        }
    }

    private static function unknownCommand(string $command): int
    {
        error("Неизвестная команда: {$command}");
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
pfm-guidelines — установка личных AI‑гайдлайнов в .ai/guidelines

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
  --target=.ai/guidelines/pepperfm
  --config=.pfm-guidelines.json
  --write-config                           записать/обновить конфиг (sync/init в no-interaction)
  --force                                  перезаписывать существующие файлы
  --dry-run                                только показать действия
  --no-interaction                         без prompts (для CI)
  --boost-update                           после sync запустить php artisan boost:update (если возможно)

Examples:
  php vendor/bin/pfm-guidelines init
  php vendor/bin/pfm-guidelines sync
  php vendor/bin/pfm-guidelines sync --no-interaction --mode=copy --presets=laravel,element-plus --write-config

TXT;

        // alert() prints a block. In fallback terminals it becomes a simple output.
        alert($text);
    }

    /**
     * Very small argv parser:
     * - supports --key=value
     * - supports --key value
     * - supports --flag
     *
     * @param array<int, string> $args
     * @return array<string, mixed>
     */
    private static function parseOptions(array $args): array
    {
        $opts = [
            'preset' => [],
        ];

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            if (!str_starts_with($arg, '-')) {
                continue;
            }

            // --key=value
            if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
                [$k, $v] = explode('=', substr($arg, 2), 2);
                self::pushOpt($opts, $k, $v);
                continue;
            }

            // --flag
            if (str_starts_with($arg, '--')) {
                $k = substr($arg, 2);

                // flags
                if (in_array($k, ['force', 'dry-run', 'no-interaction', 'help', 'write-config', 'boost-update'], true)) {
                    $opts[str_replace('-', '_', $k)] = true;
                    continue;
                }

                // --key value
                $v = $args[$i + 1] ?? null;
                if ($v !== null && !str_starts_with($v, '-')) {
                    $i++;
                    self::pushOpt($opts, $k, $v);
                    continue;
                }

                // default: treat as boolean flag
                $opts[str_replace('-', '_', $k)] = true;
                continue;
            }

            // -h / -V (minimal)
            if ($arg === '-h') {
                $opts['help'] = true;
            }
            if ($arg === '-V') {
                $opts['version'] = true;
            }
        }

        return $opts;
    }

    /** @param array<string, mixed> $opts */
    private static function pushOpt(array &$opts, string $key, string $value): void
    {
        $keyNorm = str_replace('-', '_', $key);

        if ($keyNorm === 'preset') {
            $opts['preset'][] = $value;
            return;
        }

        $opts[$keyNorm] = $value;
    }

    /** @param array<string, mixed> $opts
     *  @return array<int, string>
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

    private static function configPath(string $projectRoot, string $configRel): string
    {
        if (str_starts_with($configRel, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/?/', $configRel)) {
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
            info("[dry-run] write config: {$path}");
            return true;
        }

        return @file_put_contents($path, $json) !== false;
    }
}
