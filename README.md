# pepperfm/ai-guidelines

Небольшой Composer‑пакет для установки **личных AI‑гайдлайнов (Codex / Boost)** в проект.

Пакет хранит 2 пресета (по одному `core.md`):

- `laravel` — Codex — Laravel/Sail/MCP Guidelines (Personal Overrides)
- `nuxt-ui` — Nuxt UI — Project Guidelines (Laravel 12 + Vite + Inertia + Tailwind v4)

CLI умеет:

- выбрать пресеты интерактивно (Laravel Prompts),
- создать **symlink** или **copy** в `.ai/guidelines/...`,
- (опционально) запустить `php artisan boost:update` через флаг `--boost-update`, если проект Laravel.

## Установка

```bash
composer r --dev pepperfm/ai-guidelines
```

## Быстрый старт (интерактивно)

Из корня проекта:

```bash
vendor/bin/pfm-guidelines
```

Команда:

1) спросит какие пресеты подключить,
2) если выбран `laravel`, спросит публиковать ли `laravel/macros.md`,
3) спросит раскладку (layout): `flat-numbered` или `folders`,
4) спросит режим (symlink/copy),
5) спросит путь назначения (target; по умолчанию зависит от layout),
6) создаст/обновит файлы.

Пример для layout `flat-numbered` (по умолчанию target: `.ai/guidelines`):
```text
.ai/guidelines/01-core.md
.ai/guidelines/10-laravel.md
.ai/guidelines/11-nuxt-ui.md
.ai/guidelines/011-laravel-macros.md   (опционально)
```

Пример для layout `folders` (по умолчанию target: `.ai/guidelines/pepperfm`):
```text
.ai/guidelines/pepperfm/_core/core.md
.ai/guidelines/pepperfm/laravel/core.md
.ai/guidelines/pepperfm/laravel/macros.md   (опционально)
.ai/guidelines/pepperfm/nuxt-ui/core.md
```

Опционально: skills в `.ai/skills` (по умолчанию включено).

Также создаст конфиг в корне проекта: `.pfm-guidelines.json`.

## Синхронизация (после composer update)

```bash
vendor/bin/pfm-guidelines sync
```

## Без интерактива (CI / scripts)

```bash
vendor/bin/pfm-guidelines sync --no-interaction --mode=copy --presets=laravel,nuxt-ui
```

С автозапуском Boost обновления (если есть `artisan`):

```bash
vendor/bin/pfm-guidelines sync --boost-update
```

Доступные параметры:

- `--presets=laravel,nuxt-ui`
- `--preset=laravel` (можно указывать несколько раз)
- `--mode=symlink|copy`
- `--layout=flat-numbered|folders`
- `--target=.ai/guidelines`
- `--laravel-macros`
- `--skills[=true|false]`
- `--skills-target=.ai/skills`
- `--write-config`
- `--force` (перезаписывать существующие файлы)
- `--dry-run` (ничего не менять, только показать действия)
- `--no-interaction`
- `--boost-update` (после успешного `sync` запустить `./vendor/bin/sail artisan boost:update`, если есть Sail; иначе `php artisan boost:update`)
- `--config=.pfm-guidelines.json` (путь к конфигу)
- `-V`, `--version`

## Связка с Boost / Codex

Если проект использует **Laravel Boost**, то после установки гайдлайнов можно выполнить:

```bash
php artisan boost:update
```

Если в проекте есть **Laravel Sail**, безопаснее выполнять через:

```bash
./vendor/bin/sail artisan boost:update
```

Boost прочитает `.ai/guidelines/*` и пересоберёт `AGENTS.md` и другие файлы. Для пакетов третьих сторон актуальный контракт по-прежнему опирается на `resources/boost/guidelines/core.blade.php` и `resources/boost/skills/*/SKILL.md`.

## Команды

- `pfm-guidelines` (без аргументов) → alias для `init`
- `pfm-guidelines init` → интерактивная настройка + sync
- `pfm-guidelines sync` → применить конфиг/параметры, создать symlink/copy
- `pfm-guidelines list` → показать доступные пресеты
- `pfm-guidelines help` → справка
- `pfm-guidelines -V` / `pfm-guidelines --version` → версия CLI
