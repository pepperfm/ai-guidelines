# Repository Guidelines

## Purpose

`pepperfm/ai-guidelines` is the shared source of AI guidance used by downstream Laravel projects. Treat guideline/skill edits as product changes: stale or overly broad instructions are multiplied across every consumer.

## Architecture

```text
bin/                          # Composer CLI entrypoint
src/Cli/                      # CLI, config, installer, presets
resources/guidelines/         # authored always-on guideline sources
resources/boost/guidelines/   # generated Boost entrypoint only
resources/boost/skills/       # modular on-demand skills
resources/boost/output-styles/# output styles
scripts/                      # build helpers
```

### Source vs generated files

- Edit `resources/guidelines/**/*.md`.
- Do **not** edit `resources/boost/guidelines/core.blade.php` by hand.
- Rebuild it with `php scripts/build-boost-guidelines.php`.
- Keep authored `.md` files out of `resources/boost/guidelines/`; Boost scans that directory and would duplicate context.

## Guidance design

- Always-on guidelines contain only durable invariants.
- Detailed PHP/Laravel conventions belong in `laravel-php-style`.
- Command/test workflows belong in `laravel-sail-and-tests`.
- Fast-moving library/component APIs belong in MCP or version-aware official docs.
- Do not add a skill just to mirror external documentation.
- Project-specific file paths/classes do not belong in this shared package unless they describe this repository itself.
- If a guideline mentions an API, prefer rules that tell the agent how to determine the installed version instead of hard-coding a current major.

Nuxt UI skills are intentionally not shipped. Keep only minimal integration invariants in the `nuxt-ui` preset and rely on current MCP/docs for component API.

## Coding style

- PHP 8.3+, `declare(strict_types=1);`.
- PSR-12 formatting, 4-space indentation, one class per file.
- CLI option names use kebab-case.
- Prefer small explicit changes over adding abstraction to the installer.

## Verification

For PHP changes:

```bash
php -l src/Cli/Installer.php
php -l src/Cli/Skills.php
php -l scripts/build-boost-guidelines.php
```

For guideline changes:

```bash
php scripts/build-boost-guidelines.php
php scripts/build-boost-guidelines.php --check
```

For CLI behavior:

```bash
php bin/pfm-guidelines list
php bin/pfm-guidelines sync --dry-run --no-interaction --presets=laravel
```

The repository currently has no automated test suite. Do not claim validation that was not actually run.

## Pull requests

- Keep commit messages short and imperative.
- Explain downstream behavior changes in the PR body.
- Update `README.md` whenever CLI/preset/package behavior changes.
