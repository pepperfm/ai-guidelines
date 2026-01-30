<?php

declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Config
{
    public const VERSION = 4;

    public const DEFAULT_GUIDELINES_TARGET = '.ai/guidelines';
    public const DEFAULT_SKILLS_TARGET = '.ai/skills';
    public const DEFAULT_LAYOUT = 'flat-numbered';

    public function __construct(
        public string $mode = 'symlink', // symlink|copy
        public string $layout = self::DEFAULT_LAYOUT, // flat-numbered (Boost contract)
        public string $target = self::DEFAULT_GUIDELINES_TARGET,
        /** @var array<int, string> */
        public array $presets = ['laravel'],
        public bool $laravelMacros = false,
        public bool $skills = true,
        public string $skillsTarget = self::DEFAULT_SKILLS_TARGET,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'mode' => $this->mode,
            'layout' => $this->layout,
            'target' => $this->target,
            'presets' => array_values($this->presets),
            'laravel_macros' => $this->laravelMacros,
            'skills' => $this->skills,
            'skills_target' => $this->skillsTarget,
        ];
    }

    public static function fromArray(array $data): self
    {
        $mode = is_string($data['mode'] ?? null) ? (string) $data['mode'] : 'symlink';
        $layout = is_string($data['layout'] ?? null) ? (string) $data['layout'] : self::DEFAULT_LAYOUT;

        // Boost contract: only flat-numbered is supported. 'folders' is accepted for backwards compatibility but coerced.
        $target = is_string($data['target'] ?? null) ? (string) $data['target'] : self::DEFAULT_GUIDELINES_TARGET;
        $laravelMacros = (bool) ($data['laravel_macros'] ?? false);

        // Backwards compatible defaults: skills ON by default.
        $skills = array_key_exists('skills', $data) ? (bool) $data['skills'] : true;
        $skillsTarget = is_string($data['skills_target'] ?? null) ? (string) $data['skills_target'] : self::DEFAULT_SKILLS_TARGET;

        $presets = is_array($data['presets'] ?? null) ? array_values($data['presets']) : ['laravel'];
        $presets = array_map('strval', $presets);
        $presets = Presets::filterValid($presets);

        if (!in_array($mode, ['symlink', 'copy'], true)) {
            $mode = 'symlink';
        }
        if ($layout === 'folders') {
            $layout = self::DEFAULT_LAYOUT;
        }
        if ($layout !== self::DEFAULT_LAYOUT) {
            $layout = self::DEFAULT_LAYOUT;
        }

        // Boost contract: always install into .ai/guidelines and .ai/skills.
        $target = self::DEFAULT_GUIDELINES_TARGET;
        $skillsTarget = self::DEFAULT_SKILLS_TARGET;

        return new self(
            mode: $mode,
            layout: $layout,
            target: $target,
            presets: $presets ?: ['laravel'],
            laravelMacros: $laravelMacros,
            skills: $skills,
            skillsTarget: $skillsTarget,
        );
    }

    public function isSymlink(): bool
    {
        return $this->mode === 'symlink';
    }

    public function isFlat(): bool
    {
        return true; // Boost contract: always flat-numbered
    }
}
