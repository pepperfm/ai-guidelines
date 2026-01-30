---
name: laravel-sail-and-tests
description: 'Sail (Artisan/Composer/Bun) и тесты: команды, типовые сценарии, таймауты, правила вывода. Активируй при работе с консольными командами и CI через Sail.'
---

# Skill: Laravel — Sail, Artisan, Composer, Tests

**Версия:** 2026‑01‑30

**Когда использовать:**
- Нужны команды для Laravel/Sail.
- Нужно подсказать запуск миграций/сидов/очередей/кэшей.
- Нужно запустить/подсказать тесты.

> Если в проекте есть `.ai/guidelines/01-core.md` и `10-laravel.md` — эта skill дополняет их, но не противоречит.

---

## 1) Все команды — через Sail (MUST)

Проект работает в Docker через **Laravel Sail**.

### Разрешено
- `./vendor/bin/sail artisan ...`
- `./vendor/bin/sail composer ...`
- `./vendor/bin/sail bun ...`

### Запрещено
- `docker compose exec ...` напрямую
- хостовый `php artisan ...` (вне контейнера)
- хостовый `composer ...` (вне контейнера)

**Важно:** нельзя утверждать, что команда была выполнена (миграции применены, тесты прошли), если в ответе нет реального вывода команды.

---

## 2) Таймауты и вывод (SHOULD)

- Обычная команда: ориентир **до 60 сек**.
- Тесты/миграции: допускается **до 180 сек**.
- Если stdout становится слишком большим (ориентир: > ~200 строк / > ~64 KiB):
  1) дай короткое резюме,
  2) приложи «хвост» (последние ~80–120 строк),
  3) предложи полный лог по запросу.

---

## 3) Artisan — базовый шаблон

```bash
./vendor/bin/sail artisan <command> [options]
```

Примеры:
```bash
# Миграции
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed

# Генерация
./vendor/bin/sail artisan make:controller UserController
./vendor/bin/sail artisan make:model Order -mfc

# Очереди
./vendor/bin/sail artisan queue:work --once

# Кэши
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
```

---

## 4) Тестирование (Pest через `artisan test`)

### 4.1 Запуск

```bash
# Все тесты
./vendor/bin/sail artisan test

# Конкретный файл
./vendor/bin/sail artisan test tests/Feature/UserTest.php

# Фильтр по имени теста
./vendor/bin/sail artisan test --filter=test_user_can_login

# Компактный вывод
./vendor/bin/sail artisan test --compact
```

### 4.2 Параллельные тесты (MUST NOT по умолчанию)

`--parallel` **нельзя** предлагать/включать по умолчанию.

Разрешено **только** если пользователь **явно** подтверждает, что права БД/настройки для воркеров готовы (иначе типично ловят `SQLSTATE[HY000] [1044] Access denied`).

### 4.3 После изменений

После генерации/рефакторинга (особенно затрагивающего домен/HTTP/БД) — **SHOULD** предложить прогнать релевантные тесты.

---

## 5) Composer / bun

```bash
# Composer
./vendor/bin/sail composer i
./vendor/bin/sail composer r vendor/package

# bun
./vendor/bin/sail bun i
./vendor/bin/sail bun run dev
./vendor/bin/sail bun run build
```
