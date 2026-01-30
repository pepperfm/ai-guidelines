# PepperFM Skills

Этот каталог содержит **модульные навыки** (SKILLS), которые можно подключать по мере надобности, вместо того чтобы держать весь объём правил в `AGENTS.md` / `CLAUDE.md`.

## Формат (совместимо с Laravel Boost / Agent Skills)

- Каждый навык — это папка вида `<skill-name>/SKILL.md`.
- `<skill-name>` — `kebab-case` (например `laravel-php-style`).
- В `SKILL.md` есть YAML frontmatter с `name` и `description`.

Примеры:

- `laravel-sail-and-tests/SKILL.md`
- `laravel-php-style/SKILL.md`
- `nuxt-ui-mcp-and-docs/SKILL.md`
- `nuxt-ui-patterns/SKILL.md`
- `element-plus-guide/SKILL.md`

## Рекомендация по экономии контекста

- Не подключай все навыки сразу.
- Обычно достаточно 1–3 навыков под текущую задачу.
