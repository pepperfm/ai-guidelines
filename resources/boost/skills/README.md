# PepperFM Skills

Этот каталог содержит модульные skills для workflow, которые не должны постоянно попадать в `AGENTS.md` / `CLAUDE.md`.

## Принцип

- Guideline держит только стабильные always-on invariants.
- Skill описывает конкретный workflow или style-domain.
- API библиотек и быстро меняющиеся справочники не дублируем в skills, если их можно получить из актуального MCP / version-aware docs.
- Обычно достаточно загрузить 1–3 релевантных skills.

## Основные skills

- `laravel-php-style` — PHP/Laravel style и backend conventions.
- `laravel-sail-and-tests` — commands, Pest/PHPUnit и verification.
- `laravel-array-macros` — `Pepperfm\LaravelMacros` (опционально).
- `spatie-laravel-data` — package-specific workflow.
- `plan`, `review`, `commit` — общие agent workflows.

Nuxt UI skills намеренно не поставляются: component API нужно получать из актуального Nuxt UI MCP / официальной документации установленной версии.
