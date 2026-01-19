<?php
declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Presets
{
    private const string LARAVEL_MACROS_FLAT = '100-laravel-macros.md';

    /**
     * Preset definitions.
     *
     * - label: human friendly name
     * - flat: destination filename in "flat-numbered" layout
     *
     * @var array<string, array{label: string, flat: string}>
     */
    private const array PRESETS = [
        'laravel' => [
            'label' => 'Laravel/Sail/MCP (Codex overrides)',
            'flat' => '10-laravel.md',
        ],
        'nuxt-ui' => [
            'label' => 'Nuxt UI (Vue/Vite) inside Laravel + Inertia',
            'flat' => '11-nuxt-ui.md',
        ],
        'element-plus' => [
            'label' => 'Element Plus + Vue 3',
            'flat' => '12-element-plus.md',
        ],
    ];

    /** @return array<string, string> */
    public static function all(): array
    {
        return array_map(static function ($def) {
            return $def['label'];
        }, self::PRESETS);
    }

    public static function exists(string $presetId): bool
    {
        return array_key_exists($presetId, self::PRESETS);
    }

    public static function flatFileName(string $presetId): string
    {
        return self::PRESETS[$presetId]['flat'] ?? ($presetId . '.md');
    }

    public static function laravelMacrosFlatFileName(): string
    {
        return self::LARAVEL_MACROS_FLAT;
    }

    /**
     * @param array<int, string> $presetIds
     * @return array<int, string>
     */
    public static function filterValid(array $presetIds): array
    {
        $out = [];
        foreach ($presetIds as $id) {
            if (self::exists($id)) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }
}
