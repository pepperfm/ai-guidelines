# pepperfm/ai-guidelines

Shared AI guidelines and skills for Laravel projects using modern coding agents, Laravel Boost and MCP.

The package now separates three concerns:

- **always-on guidelines** — short, stable invariants;
- **skills** — detailed workflows/style loaded only when relevant;
- **version-aware docs / MCP** — fast-moving framework and component APIs.

## Recommended usage with Laravel Boost

For a Laravel project that already uses Boost, installing the package is enough for shared package guidance:

```bash
composer require --dev pepperfm/ai-guidelines
php artisan boost:update
```

Do **not** publish the same shared guidelines into `.ai/guidelines` unless you intentionally need local copies for another agent/tool. Keeping package guidance in one place avoids duplicated `AGENTS.md` context.

## Optional CLI

`pfm-guidelines` is kept for projects/tools that need physical guideline files in `.ai/guidelines` (symlink or copy mode).

```bash
vendor/bin/pfm-guidelines
```

Available presets:

- `laravel` — minimal Laravel/Boost/version-aware rules;
- `nuxt-ui` — minimal Laravel + Inertia integration invariants. Nuxt UI API details are intentionally fetched from current MCP/docs instead of bundled skills.

Example:

```bash
vendor/bin/pfm-guidelines sync --no-interaction --mode=symlink --presets=laravel,nuxt-ui --boost-update
```

### Skills

For the Laravel preset the CLI can publish:

- `laravel-php-style`
- `laravel-sail-and-tests`
- optional `laravel-array-macros`

Nuxt UI skills are intentionally no longer shipped. Component props/slots/examples should come from Nuxt UI MCP or official documentation matching the installed version.

## Source layout

```text
resources/
├── guidelines/                    # authored guideline sources used by the CLI
│   ├── _core/core.md
│   ├── laravel/core.md
│   ├── laravel/macros.md
│   └── nuxt-ui/core.md
└── boost/
    ├── guidelines/core.blade.php  # generated Boost package entrypoint
    └── skills/                    # modular agent skills
```

The authored markdown is deliberately **outside** `resources/boost/guidelines/`. Laravel Boost scans that directory, so storing both source markdown and the generated aggregate there duplicates the same instructions in generated agent context.

Rebuild the package guideline after changing `resources/guidelines/**`:

```bash
php scripts/build-boost-guidelines.php
```

Check without writing:

```bash
php scripts/build-boost-guidelines.php --check
```

## CLI commands

```bash
vendor/bin/pfm-guidelines init
vendor/bin/pfm-guidelines sync
vendor/bin/pfm-guidelines list
vendor/bin/pfm-guidelines help
```

Common options:

- `--presets=laravel,nuxt-ui`
- `--preset=laravel`
- `--mode=symlink|copy`
- `--layout=flat-numbered|folders`
- `--target=.ai/guidelines`
- `--laravel-macros`
- `--skills[=true|false]`
- `--skills-target=.ai/skills`
- `--write-config`
- `--force`
- `--dry-run`
- `--no-interaction`
- `--boost-update`

Configuration is stored in `.pfm-guidelines.json` when requested.

## Upgrading from older versions

Older versions stored authored guidelines under `resources/boost/guidelines/**`. If a consumer project used `pfm-guidelines` in **symlink** mode, run:

```bash
vendor/bin/pfm-guidelines sync
```

The installer repairs broken managed symlinks that point to the old package layout. Use `--force` only when an existing non-broken destination intentionally needs replacing.
