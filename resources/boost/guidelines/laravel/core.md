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
- Для данных: guaranteed key -> прямой доступ, optional array key -> `Arr::get(...)`, mixed/object path -> `data_get(...)`; не пишем `$payload['x'] ?? null` как замену helper-у.
- Helpers > Facades: если есть helper — используем helper.
- Для `str()`: UUID как строку получаем через `str()->uuid()->toString()`, а обычную PHP-строку из fluent `Stringable` — через `->value()`.
- Интерполяция строк допустима; простые `$var` и `$object->property` пишем без `{}`, более сложные выражения оставляем прямо в строке через `{...}` и не упрощаем их без причины во временные переменные или конкатенацию.
- Для "пусто / заполнено" по умолчанию предпочитаем `blank()` / `filled()`, `=== null` оставляем для проверки именно `null`, а `empty()` в основном для явной проверки пустого массива.
- `Collection` сохраняем для fluent-обработки; в `array` переходим только на границе контракта, native PHP или внешнего payload.
- В Eloquent по умолчанию предпочитаем `exists()/doesntExist()`, `value()`, `firstWhere()`, `findOrFail()/firstOrFail()` и `pluck(...)->all()`.
- В контроллерах helper-first и sugar-first: `response()->json(...)`, `to_route(...)`, `back()`; доменный слой по умолчанию кидает исключения, а не строит HTTP response.
- Не вводим временные переменные и лишние локальные рефакторы без пользы; request-like DI объект по умолчанию называется `$request`.
- Обычный `Request` и inline `$request->validate(...)` допустимы для простых кейсов; если входной слой растёт, default move — в `FormRequest`; если нужен тип — используем typed request methods.
- Для фиксированных перечислений почти всегда используем enum: сравнение по case (`=== Status::Draft`), `->value` только на границе, labels через методы enum, входные array-values через `Arr::toEnum(...)`, если доступно.
- Для исключений имя переменной всегда `$e`; не логируем/не `report(...)` исключение перед пробросом без новой полезной информации.
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
