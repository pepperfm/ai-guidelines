# Project Overview — CLI‑пакет Composer для установки личных AI‑гайдлайнов (Codex/Boost) в проекты, создающий symlink или copy в `.ai/guidelines`.
Интерактивный `init` помогает выбрать пресеты, режим и путь установки, а затем синхронизирует файлы; поведение можно закрепить в `.pfm-guidelines.json`.
Пакет поставляется как исполняемый скрипт `pfm-guidelines` и предназначен для использования в PHP/Laravel‑проектах.

## Tech Stack — какие языки, фреймворки, БД и т.п.

- PHP 8.3 (Composer library)
- `laravel/prompts` ^0.3 для интерактивных CLI‑промптов
- Без БД и веб‑фреймворка; чистый CLI

## Main Features — список ключевых фич с отсылкой к основным файлам/директориям

- CLI команды `init`, `sync`, `list`, парсинг опций и интерактивные промпты — `src/Cli/Application.php`
- Установка пресетов через symlink/copy, dry‑run, force и обработка ошибок — `src/Cli/Installer.php`
- Конфиг `.pfm-guidelines.json` (чтение/запись, версия) — `src/Cli/Config.php`
- Реестр пресетов и имена файлов для flat‑раскладки — `src/Cli/Presets.php`
- Содержимое пресетов (гайдлайны) — `resources/guidelines/*/core.md`, опциональный `resources/guidelines/laravel/macros.md`

## Architecture / Structure — кратко про слои/каталоги проекта

- `bin/pfm-guidelines` — entrypoint CLI‑скрипта
- `src/Cli/*` — логика CLI, конфиг, инсталлятор, утилиты путей
- `resources/guidelines/` — исходные markdown‑гайдлайны, которые публикуются в проект
- `vendor/` — зависимости Composer

## Development — как запускать, гонять тесты, любые важные команды

- Установить зависимости: `composer install`
- Запуск CLI из репозитория: `php bin/pfm-guidelines init` или `php bin/pfm-guidelines sync`
- В проекте‑потребителе: `vendor/bin/pfm-guidelines init` / `vendor/bin/pfm-guidelines sync`
- Автотестов в репозитории нет
