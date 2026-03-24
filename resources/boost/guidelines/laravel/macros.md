# Laravel Macros — Quick Pointer

**Версия:** 2026‑03‑24

Этот файл намеренно короткий: **полный** гайд по `Pepperfm\LaravelMacros` вынесен в SKILLS, чтобы не раздувать контекст.

## Где лежит полный гайд

- Skill: `laravel-array-macros`
- Файл: `.ai/skills/laravel-array-macros/SKILL.md`

## Когда подключать skill `laravel-array-macros`

- В задаче упоминаются `macros-for-laravel`, `MACROS_PROFILE`, `MACROS_ENABLED`.
- Работаешь с фасадом `Arr`, особенно если значение читается из массива **сразу как тип** (`int|bool|float|string|array|enum`).
- Нужно объяснить/настроить профили, политики конфликтов (`conflicts`, `unreachable`) или добавить кастомную группу.

## Короткий принцип

- `Arr::get(...)` — доступ без приведения.
- `Arr::int / bool / toFloat / toString / toArray / toEnum` — доступ **с soft-cast**.
- Если выбран макрос, внешний код не должен повторять его работу кастами, `trim`, `??` или избыточными аргументами по умолчанию.

Примеры:

```php
// Было
(int) Arr::get($payload, 'timeout', 600);

// Нужно
Arr::int($payload, 'timeout', 600);

// Было
Arr::toString($payload, 'title', null);

// Нужно
Arr::toString($payload, 'title');
```

> Общие правила (Core) см. в target: `01-core.md` (layout `flat-numbered`) или `_core/core.md` (layout `folders`).
