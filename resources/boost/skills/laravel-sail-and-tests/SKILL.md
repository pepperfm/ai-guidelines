---
name: laravel-sail-and-tests
description: 'Laravel/Sail команды и тестовый workflow: Artisan/Composer/Bun, Pest/PHPUnit version awareness, targeted tests и verification. Активируй при запуске команд или написании/изменении тестов.'
---

# Skill: Laravel — Sail, Commands & Tests

**Версия:** 2026-10-04

## Когда использовать

- Нужно запускать Artisan/Composer/Bun через Laravel Sail.
- Пишешь, исправляешь или ревьюишь Pest/PHPUnit tests.
- Нужно выбрать минимальный verification workflow после изменения backend-кода.

## 1) Среда выполнения

Если проект использует Laravel Sail, PHP/Artisan/Composer-команды запускаем через него:

```bash
./vendor/bin/sail artisan <command>
./vendor/bin/sail composer <command>
./vendor/bin/sail bun <command>
```

Не подменяй существующий project runner на `docker compose exec` или host PHP без причины. Если проект не использует Sail, следуй его локальным командам.

Нельзя заявлять, что команда была выполнена или тест прошёл, если нет реального вывода.

## 2) Version-aware Pest / PHPUnit

Перед написанием или исправлением теста:

1. Определи реальные версии `pestphp/pest`, `phpunit/phpunit` и relevant Pest plugins из `composer.lock` / Boost application info.
2. Для Laravel/Pest integration используй version-aware docs / project MCP. Если документация неоднозначна или отстаёт, проверь API установленного кода в `vendor/`.
3. Не используй методы из другого major Pest/PHPUnit по памяти.
4. Не используй deprecated API, если установленная версия предоставляет поддерживаемую замену.
5. Если IDE или static analyzer показывает `undefined method`, неправильную сигнатуру или deprecation — это незавершённая работа, даже если runtime-test случайно проходит.

Если в проекте установлен `pestphp/pest-plugin-agent`, используй его skill/workflow для быстрых behavioural probes, когда это уместно.

## 3) Запуск тестов

Сначала запускай самый узкий тест:

```bash
./vendor/bin/sail artisan test tests/Feature/UserTest.php
./vendor/bin/sail artisan test --filter=test_user_can_login
./vendor/bin/sail artisan test --compact
```

Полный suite нужен после локального зелёного результата или когда project gate явно этого требует.

Не добавляй `--parallel` по умолчанию. Используй его только если проект уже настроен для parallel testing или это явно подтверждено локальными командами/конфигурацией.

## 4) Static analysis тестов

После изменения PHP-теста:

- если project PHPStan/Larastan анализирует `tests/`, прогони анализ по изменённому тесту или тестовому каталогу;
- если установлен Pest PHPStan extension / PHPUnit extension, используй существующий project command;
- если `tests/` исключены из static analysis, не заявляй, что IDE/type-level API проверены: укажи этот gap;
- не добавляй ignore/suppression только чтобы скрыть deprecated/undefined test API.

Пример, если проект это поддерживает:

```bash
./vendor/bin/sail bin phpstan analyse tests/Feature/UserTest.php
```

## 5) Verification order

Для backend-изменения нормальный порядок:

1. affected test / narrow filter;
2. static analysis изменённых PHP-файлов, если настроен;
3. formatter/linter project command;
4. broader project gate (`make check`, `make do-anal`, full suite и т.п.) — если он принят в репозитории.

Не создавай отдельный verification script, если существующие tests/checks уже покрывают задачу.

## 6) Composer / Bun

```bash
./vendor/bin/sail composer install
./vendor/bin/sail composer require vendor/package

./vendor/bin/sail bun install
./vendor/bin/sail bun run dev
./vendor/bin/sail bun run build
```
