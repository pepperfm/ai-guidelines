{{-- AUTO-GENERATED FILE. DO NOT EDIT DIRECTLY. --}}
{{-- This file is generated from markdown sources in resources/boost/guidelines/**/*.md --}}
{{-- Run: php scripts/build-boost-guidelines.php --}}
{{-- Checksum: 390fa15309f77d0aa8bce20f36941fea9e32fc8c --}}
@verbatim
<!-- BEGIN: _core/core.md -->

# Core — Project Guidelines (MUST)

**Версия:** 2026‑01‑30

Этот файл содержит **общие правила**, применимые ко всем проектам в репозитории (Laravel / Inertia / Nuxt UI / Vite и т.д.).

## Где искать файлы после установки (SHOULD)

Этот пакет умеет раскладывать гайдлайны двумя способами (зависит от `pfm-guidelines --layout`):

- `flat-numbered`: в target лежат файлы вроде `01-core.md`, `10-laravel.md`, `11-nuxt-ui.md` (и `011-laravel-macros.md`, если включено).
- `folders`: в target лежат файлы вроде `_core/core.md`, `laravel/core.md`, `nuxt-ui/core.md` (и `laravel/macros.md`, если включено).

---

## 1) Приоритеты инструкций (MUST)

- **MUST > SHOULD.** При конфликте обязателен к исполнению MUST.
- Эти гайдлайны **выше** общих туториалов/примеров из интернета, если не указано иное.
- Если загружено несколько гайдлайнов, действует **каскад**: более специфичный (обычно ближе к рабочей директории) имеет больший приоритет.
- Если пользователь **явно** просит отступить от правил, это допустимо **только если** не нарушает MUST/безопасность/песочницу.

---

## 2) Язык и тон (MUST)

- **Русский — язык общения по умолчанию.**
- Имена компонентов/props/опций/слотов, названия классов/файлов, ключи `.env`, команды CLI, тексты исключений — **не переводить**.
- Для длинных англоязычных логов/трейсов:
  1) сначала дать короткое русское резюме «что сломалось и где»;
  2) затем привести небольшой релевантный фрагмент оригинала (см. §4).

---

## 3) Контейнер и выполнение команд (MUST)

- Проект работает в **Laravel Sail**. Все PHP/Artisan‑команды запускаются через `./vendor/bin/sail artisan ...`.
- **Не использовать** `docker compose exec ...` напрямую — только Sail‑обёртку.
- Примеры:
  - Миграции: `./vendor/bin/sail artisan migrate`
  - Тесты: `./vendor/bin/sail artisan test`
  - Любая Artisan‑команда: `./vendor/bin/sail artisan <command>`
  - Composer: `./vendor/bin/sail composer ...`
  - Фронтенд (bun): `./vendor/bin/sail bun ...`
- **Нельзя заявлять**, что команда была запущена/миграции применены/тесты пройдены, если это не подтверждено выводом команды.

---

## 4) Дисциплина вывода (SHOULD)

- Не вставлять в ответ «простыню» логов.
- По умолчанию достаточно:
  - 5–15 строк контекста вокруг ошибки **и/или**
  - последние 20–60 строк вывода (tail), если ошибка в конце.
- Если нужен полный лог — сначала спросить, либо предложить сохранить лог в файл и приложить путь.

---

## 5) Источники правды и актуальность (SHOULD)

- Приоритет источников:
  1) код и конфигурация репозитория;
  2) MCP‑серверы проекта;
  3) локальные «зеркала» документации в репозитории;
  4) официальные доки библиотек/фреймворков.
- При сомнениях по версии:
  - уточнить установленную версию (`composer.lock`, `package.json`) и сверять с соответствующей веткой документации.

---

## 6) Без «фоновых обещаний» (MUST)

- Нельзя отвечать в стиле «сделаю позже», «подождите», «вернусь с результатом».
- Либо выполнить задачу прямо сейчас, либо честно описать ограничение и дать следующий лучший вариант.

---

## 7) Skills (SHOULD)

Если в проекте есть каталог `.ai/skills/` — это **набор модульных навыков**.

- **Не подгружай все навыки сразу.** Выбирай 1–3 релевантных под задачу (чтобы экономить контекст/токены).
- Если задача затрагивает конкретный стек — сначала подключай профильный skill (например, Nuxt UI / Laravel стиль).

<!-- END: _core/core.md -->

---

<!-- BEGIN: laravel/core.md -->

# Codex — Laravel/Sail Guidelines (Lite)

**Версия:** 2026‑03‑25

Этот документ — **короткая версия** Laravel‑правил: только MUST/ограничения.
Детальные примеры и разъяснения вынесены в `.ai/skills/**` (SKILLS), чтобы экономить контекст/токены.

> Общие правила (Core) см. в target: `01-core.md` (layout `flat-numbered`) или `_core/core.md` (layout `folders`).

---

## 1) Skills (подключай по необходимости)

- `laravel-sail-and-tests` — запуск команд и тестов через Sail + правила таймаутов/вывода.
- `laravel-php-style` — подробный PHP/Laravel стиль: strict_types, helpers, Arr::get, FQCN, импорты, контроллеры.
- `laravel-array-macros` — гайд по Pepperfm\LaravelMacros для `Arr::*` и soft-cast accessors (если используется).

---

## 2) MUST

- Все команды запускаются через **Laravel Sail** (`./vendor/bin/sail ...`).
- Если в проекте доступен Laravel Boost MCP / Docs API, сначала используем его как источник правды по Laravel ecosystem, а не «память» модели.
- Нельзя утверждать, что команда была выполнена, если нет подтверждённого вывода.
- Каждый PHP-файл начинается с `declare(strict_types=1);`.
- Все публичные методы имеют явные return type'ы (для HTTP — конкретные типы ответа).
- Для вендорных типов в сигнатурах — **inline FQCN** (не импортировать ради сокращения).
- Для опциональных ключей массива — `Arr::get(...)`; если нужен soft-cast и подключён `pepperfm/macros-for-laravel` — `Arr::toString(...)` / `Arr::int(...)` / `Arr::bool(...)` (см. skill `laravel-array-macros`).
- Helpers > Facades: если есть helper — используем helper.
- Для `str()`: UUID как строку получаем через `str()->uuid()->toString()`, а обычную PHP-строку из fluent `Stringable` — через `->value()`.
- Интерполяция строк допустима; простые `$var` и `$object->property` пишем без `{}`, более сложные выражения оставляем прямо в строке через `{...}` и не упрощаем их без причины во временные переменные или конкатенацию.
- Импорты держим в стабильном порядке; если импортирован родительский класс (`extends BaseClass`), он идёт первым среди всех `use`-импортов файла.
- Используем проектные хелперы: `user()`, `when()`, `valueOrDefault()`, `db()`.
- Контроллеры тонкие, валидация — через `FormRequest`.
- Не читать `env()` в рантайме — только `config()`.
- Доступ к БД: Eloquent по умолчанию; при Query Builder/транзакциях — `db()`.

---

## 3) MUST NOT

- Не использовать `docker compose exec` напрямую.
- Не запускать `php artisan`/`composer` на хосте (вне контейнера).
- Не предлагать Pest `--parallel` без явного подтверждения готовых прав/настроек БД.
- Не использовать фасады, если существует эквивалентный helper.

---

## 4) Быстрые команды (шпаргалка)

> Детали, таймауты и вывод — в skill `laravel-sail-and-tests`.

```bash
# Artisan
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test

# Composer
./vendor/bin/sail composer i

# Frontend
./vendor/bin/sail bun run dev
```

<!-- END: laravel/core.md -->

---

<!-- BEGIN: laravel/macros.md -->

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

<!-- END: laravel/macros.md -->

---

<!-- BEGIN: nuxt-ui/core.md -->

# Nuxt UI — Project Guidelines (Lite)

**Версия:** 2026‑01‑30

Этот файл — **тонкий**: только MUST/ограничения по Nuxt UI в стеке *Laravel + Inertia + Vite + Tailwind*.
Детальная интеграция, паттерны и примеры вынесены в `.ai/skills/nuxt-ui-*`, чтобы экономить контекст/токены.

> Общие правила (Core) см. в target: `01-core.md` (layout `flat-numbered`) или `_core/core.md` (layout `folders`). Laravel‑правила: `10-laravel.md` или `laravel/core.md`.

---

## 1) Skills (подключай по необходимости)

- `nuxt-ui-mcp-and-docs` — работа с MCP-сервером Nuxt UI + локальное зеркало доков.
- `nuxt-ui-integration` — установка, `vite.config.ts`, `app.ts`, CSS, `UApp`, `isolate`.
- `nuxt-ui-patterns` — архитектура UI, overlays, формы, темизация, примеры, TL;DR.

---

## 2) MUST

- **Источник правды по Nuxt UI — MCP**: при вопросах про компоненты/props/slots сначала используем MCP `nuxt-ui`.
- Экономим контекст: через MCP просим только нужное (`get_component_metadata`, `sections=...`).
- Если MCP недоступен и в проекте есть `.ai/nuxtui/` — это локальное зеркало доков, используем его.
- В `vite.config.ts` Nuxt UI в режиме Inertia: `ui({ router: 'inertia' })`.
- Корневой layout оборачиваем в `<UApp>`.
- В Blade/Inertia‑корне ставим класс `isolate` (чтобы не ломались overlay‑слои/z-index).
- Не импортируем `useToast()` / `useOverlay()` вручную: они auto-import.
- Programmatic overlays (`overlay.create(...)`) держим рядом с триггером (страница/компонент), composables — только для бизнес-логики.

---

## 3) MUST NOT

- Не подключать `vue-router`, если проект работает с `router: 'inertia'` и роутинг обеспечивает Inertia.
- Не строить UI из «голых div+border», если есть эквивалентный компонент Nuxt UI.
- Не тащить огромные куски доков в ответ: даём краткое резюме + ссылку/указание на skill.

<!-- END: nuxt-ui/core.md -->
@endverbatim
