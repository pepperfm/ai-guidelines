# Repository Guidelines

## Project Structure & Module Organization
- `src/` holds the PHP source (PSR-4 `PepperFM\\AiGuidelines\\`), with CLI logic in `src/Cli/`.
- `bin/pfm-guidelines` is the CLI entrypoint used by Composer.
- `resources/guidelines/` contains preset Markdown files such as `laravel/core.md`, `nuxt-ui/core.md`, `element-plus/core.md`, and shared files under `_core/`.
- `composer.json` defines dependencies and autoloading; `vendor/` is generated after install.

## Build, Test, and Development Commands
- `composer install` installs PHP dependencies for local development.
- `bin/pfm-guidelines` runs the CLI from source (useful while developing in this repo).
- `vendor/bin/pfm-guidelines list` prints available presets.
- `vendor/bin/pfm-guidelines sync --dry-run` previews file operations without writing.

## Coding Style & Naming Conventions
- PHP 8.3+, `declare(strict_types=1);` at the top of PHP files.
- Follow PSR-12: 4-space indentation, one class per file, `PascalCase` class names, namespaces matching folder structure.
- CLI options use kebab-case (`--no-interaction`, `--dry-run`).
- Preset files live at `resources/guidelines/<preset>/core.md`; keep filenames lowercase and consistent.

## Testing Guidelines
- No automated test suite is currently included.
- Validate changes by running the CLI against a scratch project and inspecting generated files; prefer `--dry-run` when iterating.
- If you add tests, place them under `tests/` and document the command used to run them.

## Commit & Pull Request Guidelines
- Commit messages are short, imperative, and lowercase (e.g., `fix installer`, `upd rule`).
- PRs should describe behavior changes, list commands run, and include sample CLI output or file diffs when presets change.
- Update `README.md` for any user-facing CLI or preset changes.

## Security & Configuration Notes
- The CLI writes `.pfm-guidelines.json` and target guideline files; avoid committing user-specific config into this repository.
