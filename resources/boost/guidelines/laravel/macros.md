# Laravel Macros — Quick Pointer

**Версия:** 2026‑01‑30

Этот файл намеренно короткий: **полный** гайд по `Pepperfm\LaravelMacros` вынесен в SKILLS, чтобы не раздувать контекст.

## Где лежит полный гайд

- Skill: `laravel-macros`
- Файл: `.ai/skills/laravel-macros/SKILL.md`

## Когда подключать skill `laravel-macros`

- В задаче упоминаются `macros-for-laravel`, `MACROS_PROFILE`, `MACROS_ENABLED`.
- Работаешь с фасадом `Arr`, или нужно приводить к типу получаемые из массива значения, по типу `(string) Arr::get(...)` -> `Arr::toString(...)` etc.
- Нужно объяснить/настроить профили, политики конфликтов (`conflicts`, `unreachable`) или добавить кастомную группу.

> Общие правила (Core) см. в target: `01-core.md` (layout `flat-numbered`) или `_core/core.md` (layout `folders`).
