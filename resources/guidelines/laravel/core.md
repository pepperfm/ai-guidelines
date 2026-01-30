# Codex — Laravel/Sail Guidelines (Lite)

**Версия:** 2026‑01‑30

Этот документ — **короткая версия** Laravel‑правил: только MUST/ограничения.
Детальные примеры и разъяснения вынесены в `.ai/skills/**` (SKILLS), чтобы экономить контекст/токены.

> Общие правила (приоритеты, язык, контейнер, лимиты логов) см. `.ai/guidelines/01-core.md`.

---

## 1) Skills (подключай по необходимости)

- `laravel-sail-and-tests` — запуск команд и тестов через Sail + правила таймаутов/вывода.
- `laravel-php-style` — подробный PHP/Laravel стиль: strict_types, helpers, Arr::get, FQCN, импорты, контроллеры.
- `laravel-macros` — гайд по Pepperfm\LaravelMacros (если используется).

---

## 2) MUST

- Все команды запускаются через **Laravel Sail** (`./vendor/bin/sail ...`).
- Нельзя утверждать, что команда была выполнена, если нет подтверждённого вывода.
- Каждый PHP-файл начинается с `declare(strict_types=1);`.
- Все публичные методы имеют явные return type'ы (для HTTP — конкретные типы ответа).
- Для вендорных типов в сигнатурах — **inline FQCN** (не импортировать ради сокращения).
- Для опциональных ключей массива — `Illuminate\Support\Arr::get(...)`.
- Helpers > Facades: если есть helper — используем helper.
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
