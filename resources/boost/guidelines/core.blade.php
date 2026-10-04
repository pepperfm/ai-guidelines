{{-- AUTO-GENERATED FILE. DO NOT EDIT DIRECTLY. --}}
{{-- This file is generated from markdown sources in resources/guidelines/**/*.md --}}
{{-- Run: php scripts/build-boost-guidelines.php --}}
{{-- Checksum: f3f6f8b53760131889518f650b6e3d0dca614531 --}}
@verbatim
<!-- BEGIN: _core/core.md -->

# Core — Agent Guidelines

**Версия:** 2026-10-04

Этот guideline намеренно короткий. В always-on контексте должны оставаться только стабильные правила; подробные workflow и style-конвенции живут в skills.

## 1) Источники правды

Приоритет для технических решений:

1. текущий код и конфигурация репозитория;
2. lock-файлы и реально установленные зависимости (`composer.lock`, `package.json` / lockfile, `vendor/`, `node_modules/`, если доступны);
3. project tools / MCP и version-aware documentation;
4. официальная документация нужной версии;
5. память модели — только как fallback.

Перед использованием version-sensitive API сначала определи установленную версию. Не переноси синтаксис между major-версиями по памяти.

Если guideline или skill ссылается на путь/класс, которого больше нет, не считай ссылку архитектурной истиной: найди актуальную реализацию в репозитории и продолжай от неё.

## 2) Skills и контекст

- Не загружай все skills заранее. Обычно достаточно 1–3 навыков, релевантных текущей задаче.
- Project-local rules/skills имеют приоритет над shared package guidance.
- Guideline отвечает на вопрос «какие инварианты соблюдать», skill — «как выполнять конкретный workflow».
- Большие справочники API не копируем в guidelines: для них используем MCP / version-aware docs.

## 3) Изменения кода

- Перед изменением посмотри соседний код и существующие паттерны проекта.
- Не добавляй abstraction/dependency только ради «чистоты», если текущей задаче она не нужна.
- Не ослабляй тесты ради зелёного прогона: не удаляй проверки, не заменяй их бессодержательными assertions и не suppress-ь реальные ошибки без объяснимой причины.
- Не используй deprecated API, если в установленной версии есть поддерживаемая замена.

## 4) Проверка результата

- Сначала запускай самый узкий релевантный check/test, затем более широкий gate, если он нужен проекту.
- Если проект уже имеет lint/static-analysis/test команды, используй их вместо одноразовых verification scripts.
- Нельзя утверждать, что тесты, анализ или команды прошли, если это не подтверждено реальным выводом.

<!-- END: _core/core.md -->

---

<!-- BEGIN: laravel/core.md -->

# Laravel — Shared Guidelines (Lite)

**Версия:** 2026-10-04

Этот файл содержит только always-on правила. Детали PHP/Laravel style и workflow команд/тестов вынесены в skills.

## Skills

Подключай по необходимости:

- `laravel-php-style` — PHP/Laravel style, data access, Eloquent, requests, HTTP/errors, enums и naming.
- `laravel-sail-and-tests` — Sail/Artisan/Composer/Bun, Pest/PHPUnit, targeted test runs и verification.
- `laravel-array-macros` — только если проект использует `pepperfm/macros-for-laravel` и задача касается `Arr::*` macros.

## Version-aware Laravel

- Версии PHP, Laravel, Pest, PHPUnit и Laravel ecosystem packages определяй из текущего проекта (`composer.lock`, Boost application info), а не из памяти модели.
- Если доступен Laravel Boost MCP / `search-docs`, используй его первым для Laravel ecosystem.
- Для API стороннего Composer-пакета при сомнении сверяй установленную версию и локальный `vendor/`; документация должна соответствовать этой версии.
- Если IDE/static analyzer помечает вызов как deprecated или undefined, исправь вызов на API установленной версии. Не suppress-ь предупреждение только ради прохождения проверки.
- Не копируй в проект API-примеры из другого major Laravel/Pest/PHPUnit без проверки совместимости.

## Project conventions

- Сначала следуй локальному коду и project-local rules; shared guideline не должен переопределять осознанную архитектуру конкретного приложения.
- Для backend-изменений загрузи `laravel-php-style`, вместо того чтобы держать подробный style guide в always-on контексте.
- Для команд и тестов загрузи `laravel-sail-and-tests`; там находятся правила запуска и проверки.

<!-- END: laravel/core.md -->

---

<!-- BEGIN: nuxt-ui/core.md -->

# Nuxt UI — Minimal Integration Guidelines

**Версия:** 2026-10-04

Этот preset намеренно не содержит локального справочника компонентов и не устанавливает Nuxt UI skills. API Nuxt UI меняется достаточно быстро, поэтому props/slots/examples нужно брать из актуального MCP или официальной документации установленной версии.

## Source of truth

- Сначала определи установленную версию `@nuxt/ui` из package manager lockfile.
- Для component API, props, slots и examples используй Nuxt UI MCP / официальные version-matching docs, а не память модели.
- Не поддерживай локальные копии больших фрагментов Nuxt UI docs в этом пакете.

## Laravel + Inertia invariants

- Если проект использует Nuxt UI в Vue/Vite + Inertia режиме, сохраняй существующую integration-схему проекта; не добавляй `nuxt.config.ts` или Nuxt runtime без явной причины.
- Не добавляй `vue-router`, если навигацией управляет Inertia.
- Корневой UI provider (`UApp`) и текущую настройку Vite/Nuxt UI считай частью project contract и проверяй по реальному коду перед изменением.
- Предпочитай компоненты Nuxt UI самописным заменам там, где библиотека уже предоставляет подходящий компонент.

<!-- END: nuxt-ui/core.md -->
@endverbatim
