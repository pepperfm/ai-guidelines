# Project Overview

`pepperfm/ai-guidelines` is a Composer package that provides shared AI guidelines/skills for Laravel Boost and an optional CLI for publishing project-local `.ai/guidelines` files.

## Tech Stack

- PHP 8.3+
- `laravel/prompts` for CLI interaction
- no database or web runtime

## Architecture

- `resources/guidelines/` — authored guideline sources used by the CLI and build script.
- `resources/boost/guidelines/core.blade.php` — generated Laravel Boost package guideline; never edit directly.
- `resources/boost/skills/` — modular skills exposed to agents.
- `src/Cli/` — CLI configuration, preset selection and symlink/copy installer.
- `scripts/build-boost-guidelines.php` — compiles selected authored guidelines into the single Boost package entrypoint.

Keep authored markdown outside `resources/boost/guidelines/`: Boost scans that directory and duplicate source files would duplicate agent context.

## Product rules

- Always-on guidelines must stay small and stable.
- Detailed workflows/style belong in skills.
- Fast-moving library API documentation belongs in MCP/version-aware official docs, not copied skills.
- Nuxt UI does not ship package skills; its component API comes from current docs/MCP.
- Changes to shared guidelines/skills affect every consuming project, so avoid project-specific paths and transient implementation details.

## Development

```bash
composer install
php scripts/build-boost-guidelines.php --check
php bin/pfm-guidelines list
php bin/pfm-guidelines sync --dry-run --no-interaction --presets=laravel
```

There is currently no automated test suite; syntax-check modified PHP files and validate the generated Boost guideline before merging.
