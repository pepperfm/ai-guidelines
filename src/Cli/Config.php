<?php
declare(strict_types=1);

namespace PepperFM\AiGuidelines\Cli;

final class Config
{
    public const VERSION = 1;

    public function __construct(
        public string $mode = 'symlink', // symlink|copy
        public string $target = '.ai/guidelines/pepperfm',
        /** @var array<int, string> */
        public array $presets = ['laravel'],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'mode' => $this->mode,
            'target' => $this->target,
            'presets' => array_values($this->presets),
        ];
    }

    public static function fromArray(array $data): self
    {
        $mode = is_string($data['mode'] ?? null) ? (string) $data['mode'] : 'symlink';
        $target = is_string($data['target'] ?? null) ? (string) $data['target'] : '.ai/guidelines/pepperfm';
        $presets = is_array($data['presets'] ?? null) ? array_values($data['presets']) : ['laravel'];

        $presets = array_map('strval', $presets);
        $presets = Presets::filterValid($presets);

        return new self(
            mode: $mode,
            target: $target,
            presets: $presets ?: ['laravel'],
        );
    }

    public function isSymlink(): bool
    {
        return $this->mode === 'symlink';
    }
}
