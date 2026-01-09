<?php

declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Config
{
    public const int VERSION = 2;

    public function __construct(
        public string $mode = 'symlink', // symlink|copy
        public string $layout = 'flat-numbered', // flat-numbered|folders
        public string $target = '.ai/guidelines',
        /** @var array<int, string> */
        public array $presets = ['laravel'],
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
        ];
    }

    public static function fromArray(array $data): self
    {
        $mode = is_string($data['mode'] ?? null) ? (string) $data['mode'] : 'symlink';
        $layout = is_string($data['layout'] ?? null) ? (string) $data['layout'] : 'flat-numbered';
        $target = is_string($data['target'] ?? null) ? (string) $data['target'] : '.ai/guidelines';

        $presets = is_array($data['presets'] ?? null) ? array_values($data['presets']) : ['laravel'];
        $presets = array_map('strval', $presets);
        $presets = Presets::filterValid($presets);

        if (!in_array($mode, ['symlink', 'copy'], true)) {
            $mode = 'symlink';
        }
        if (!in_array($layout, ['flat-numbered', 'folders'], true)) {
            $layout = 'flat-numbered';
        }

        return new self(
            mode: $mode,
            layout: $layout,
            target: $target,
            presets: $presets ?: ['laravel'],
        );
    }

    public function isSymlink(): bool
    {
        return $this->mode === 'symlink';
    }

    public function isFlat(): bool
    {
        return $this->layout === 'flat-numbered';
    }
}
