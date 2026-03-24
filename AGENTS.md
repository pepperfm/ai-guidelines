# Repository Guidelines

## Project Structure & Module Organization
- `src/` holds the PHP source (PSR-4 `PepperFM\\AiGuidelines\\`), with CLI logic in `src/Cli/`.
- `bin/pfm-guidelines` is the CLI entrypoint used by Composer.
- `resources/boost/guidelines/` contains preset Markdown files such as `laravel/core.md`, `nuxt-ui/core.md`, and shared files under `_core/`.
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
- Preset files live at `resources/boost/guidelines/<preset>/core.md`; keep filenames lowercase and consistent.

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

## Project Overview
`pepperfm/ai-guidelines` is a Composer CLI package that installs guideline/skill presets into consumer projects via symlink or copy modes.

## Repository Role
- This repository is the centralized source of truth for shared AI guidelines and skills used across multiple downstream Laravel projects.
- Changes to `resources/boost/guidelines/*` and `resources/boost/skills/*` are product changes for consumer projects, not just local documentation edits.
- When working here, optimize for portable, explicit, agent-consumable instructions that will be executed in other repositories and sessions.
- Prefer operational guidance over explanatory prose: downstream agents need decision rules, anti-patterns, and canonical rewrites more than narrative context.
- Before changing guideline or skill text, evaluate downstream impact: ambiguity or weak wording here will be multiplied across all projects that install this package.

## Tech Stack
- **Language:** PHP 8.3
- **Framework:** Composer package + `laravel/prompts`
- **Database:** None
- **ORM:** None

## Project Structure
```text
bin/                          # CLI entrypoint
src/Cli/                      # Core CLI logic and installer modules
resources/boost/guidelines/   # Guideline preset markdown files
resources/boost/skills/       # Skill preset files
scripts/                      # Build helper scripts
.codex/skills/                # Local Codex skills for this repo
.claude/skills/               # Local Claude skills for this repo
```

## Key Entry Points
| File | Purpose |
|------|---------|
| `bin/pfm-guidelines` | Executable entrypoint configured in Composer `bin` |
| `src/Cli/Application.php` | CLI command dispatch, option parsing, interactive flow |
| `src/Cli/Installer.php` | File publication engine (`symlink`/`copy`, dry-run, force) |
| `src/Cli/Config.php` | `.pfm-guidelines.json` config model and IO |
| `src/Cli/Skills.php` | Skill list selection by chosen presets |
| `composer.json` | Package metadata, dependencies, autoload, binary declaration |

## Documentation
| Document | Path | Description |
|----------|------|-------------|
| README | `README.md` | Package usage, commands, and examples |
| Repository Rules | `AGENTS.md` | Working conventions for contributors and agents |
| Overview Guideline | `.ai/guidelines/00-project-overview.md` | Existing concise project context |

## AI Context Files
| File | Purpose |
|------|---------|
| `AGENTS.md` | Repository rules + project map for agents |
| `.ai-factory/DESCRIPTION.md` | Project specification and detected stack |
| `.ai-factory/ARCHITECTURE.md` | Architecture decisions and dependency rules |
| `.mcp.json` | Project MCP server configuration |

## Working In This Repo
- Do not treat this package like an ordinary app repo with project-local conventions only.
- The primary deliverable is high-quality shared guidance assets plus the CLI that distributes them.
- If a user says that Codex behaves poorly in other Laravel projects using this package, inspect and improve the published skills/guidelines first, not just the local session behavior.
- For Laravel style or macro issues, prefer updating `resources/boost/skills/laravel-php-style/SKILL.md` and `resources/boost/skills/laravel-array-macros/SKILL.md` when the problem is systemic across projects.
