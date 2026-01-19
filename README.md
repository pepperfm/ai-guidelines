# pepperfm/ai-guidelines

Небольшой Composer‑пакет для установки **личных AI‑гайдлайнов (Codex / Boost)** в проект.

Пакет хранит 3 пресета (по одному `core.md`):

- `laravel` — Codex — Laravel/Sail/MCP Guidelines (Personal Overrides)
- `nuxt-ui` — Nuxt UI — Project Guidelines (Laravel 12 + Vite + Inertia + Tailwind v4)
- `element-plus` — Element Plus + Vue 3

CLI умеет:

- выбрать пресеты интерактивно (Laravel Prompts),
- создать **symlink** или **copy** в `.ai/guidelines/...`,
- (опционально) запустить `php artisan boost:update`, если проект Laravel.

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
3) спросит режим (symlink/copy),
4) спросит путь назначения (по умолчанию: `.ai/guidelines`),
5) создаст/обновит файлы вида:

```text
.ai/guidelines/laravel/core.md
.ai/guidelines/nuxt-ui/core.md
.ai/guidelines/element-plus/core.md
```

Опционально: `laravel/macros.md` (если включено в prompt).

Также создаст конфиг в корне проекта: `.pfm-guidelines.json`.

## Синхронизация (после composer update)

```bash
vendor/bin/pfm-guidelines sync
```

## Без интерактива (CI / scripts)

```bash
vendor/bin/pfm-guidelines sync --no-interaction --mode=copy --presets=laravel,element-plus
```

Доступные параметры:

- `--presets=laravel,nuxt-ui,element-plus`
- `--mode=symlink|copy`
- `--target=.ai/guidelines`
- `--laravel-macros`
- `--force` (перезаписывать существующие файлы)
- `--dry-run` (ничего не менять, только показать действия)
- `--config=.pfm-guidelines.json` (путь к конфигу)

## Связка с Boost / Codex

Если проект использует **Laravel Boost**, то после установки гайдлайнов можно выполнить:

```bash
php artisan boost:update
```

Boost прочитает `.ai/guidelines/*` и пересоберёт `AGENTS.md` и другие файлы.

## Команды

- `pfm-guidelines` (без аргументов) → alias для `init`
- `pfm-guidelines init` → интерактивная настройка + sync
- `pfm-guidelines sync` → применить конфиг/параметры, создать symlink/copy
- `pfm-guidelines list` → показать доступные пресеты
- `pfm-guidelines help` → справка
