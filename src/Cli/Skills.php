<?php

declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Skills
{
    /**
     * Skill folders (kebab-case) grouped by preset.
     *
     * These names must match:
     * - directory name: resources/skills/<skill-name>
     * - installed path: .ai/skills/<skill-name>
     * - YAML frontmatter in SKILL.md: name: <skill-name>
     *
     * @var array<string, array<int, string>>
     */
    private const BY_PRESET = [
        'laravel' => [
            'laravel-sail-and-tests',
            'laravel-php-style',
        ],
        'nuxt-ui' => [
            'nuxt-ui-mcp-and-docs',
            'nuxt-ui-integration',
            'nuxt-ui-patterns',
        ],
        'element-plus' => [
            'element-plus-guide',
        ],
    ];

    /**
     * @return array<int, string> skill names (directory names)
     */
    public static function forConfig(Config $config): array
    {
        $skills = [];

        foreach ($config->presets as $presetId) {
            foreach (self::BY_PRESET[$presetId] ?? [] as $skill) {
                $skills[] = $skill;
            }
        }

        // Optional skill: only if macros are enabled.
        if ($config->laravelMacros && in_array('laravel', $config->presets, true)) {
            $skills[] = 'laravel-macros';
        }

        return array_values(array_unique($skills));
    }
}
