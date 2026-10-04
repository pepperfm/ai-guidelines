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
