<?php
declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Presets
{
    /** @var array<string, string> */
    private const array PRESETS = [
        'laravel' => 'Laravel/Sail/MCP (Codex overrides)',
        'nuxt-ui' => 'Nuxt UI (Vue/Vite) inside Laravel + Inertia',
        'element-plus' => 'Element Plus + Vue 3',
    ];

    /** @return array<string, string> */
    public static function all(): array
    {
        return self::PRESETS;
    }

    public static function exists(string $presetId): bool
    {
        return array_key_exists($presetId, self::PRESETS);
    }

    /** @param array<int, string> $presetIds
     *  @return array<int, string>
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
