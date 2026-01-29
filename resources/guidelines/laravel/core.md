# Codex — Laravel/Sail Guidelines (Personal Overrides)

**Версия:** 2026‑01‑29

This document defines how Codex must behave in our Laravel/Sail projects.

You are expected to:
- generate production‑quality Laravel/PHP code that matches this style guide,
- run Artisan commands and tests through `./vendor/bin/sail artisan`,
- respect project constraints like PHP version (8.3 vs 8.4) and DB permissions.

> Общие правила (приоритеты, язык, контейнер, лимиты логов) см. `.ai/guidelines/*core.md`.

**Assumptions:**
- Laravel 12+ style,
- strict types,
- Pest tests,
- Sail for local dev,
- project helpers available: `user()`, `when()`, `valueOrDefault()`, `db()` (см. §4.3).

---

## 1) Agent Behavior / Boundaries

### 1.1 Все команды — через Sail

Проект работает в Docker‑контейнере через **Laravel Sail**. Все PHP/Artisan‑команды выполняются через `./vendor/bin/sail`:

```bash
# Artisan‑команды
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan make:model User
./vendor/bin/sail artisan queue:work

# Composer
./vendor/bin/sail composer i
./vendor/bin/sail composer r package/name

# Фронтенд (bun)
./vendor/bin/sail bun i
./vendor/bin/sail bun run dev
./vendor/bin/sail bun run build
```

**Не использовать:**
- `docker compose exec ...` напрямую
- хостовый `php artisan ...` (вне контейнера)
- хостовый `composer ...` (вне контейнера)

### 1.2 Таймауты и вывод

- Стандартный запуск команды: **до 60 с**; для тестов/миграций допускается до **180 с**.
- Если stdout > **64 KiB** или > **200 строк**, агент **MUST**:
    1) дать краткое резюме,
    2) приложить "хвост" (последние ~100 строк),
    3) предложить полный лог по запросу.

---

## 2) Running Artisan Commands

### 2.1 Базовый шаблон

Любая Artisan‑команда выполняется через Sail:

```bash
./vendor/bin/sail artisan <command> [options]
```

**Примеры:**
```bash
# Миграции
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed

# Генерация
./vendor/bin/sail artisan make:controller UserController
./vendor/bin/sail artisan make:model Order -mfc

# Очереди
./vendor/bin/sail artisan queue:work --once

# Кэш
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
```

---

## 3) Testing

### 3.1 Как запускать тесты

Тесты запускаются через Sail:

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

### 3.2 Параллельные тесты

По умолчанию **запрещено** предлагать `--parallel`. Разрешено **только** если пользователь **явно** сообщил, что настроены права БД для воркеров (иначе вероятна `SQLSTATE[HY000] [1044] Access denied`).

### 3.3 После изменений кода

После генерации/рефакторинга агент **SHOULD** предложить прогнать тесты и выдать краткое резюме результатов.

---

## 4) PHP & Laravel Style

### 4.1 Strict types
Каждый PHP‑файл **начинается** с:
```php
<?php

declare(strict_types=1);

```
Ровно **одна** пустая строка после `declare`, затем `namespace`. В файле — один публичный класс.

### 4.2 Namespace / классы
- PSR‑4 (`App\Http\Controllers\...`, `App\Services`, `App\Models`, …).
- Один публичный класс на файл.

### 4.3 Проектные хелперы (использовать по умолчанию)
В проекте доступны хелперы:

```php
user(?string $guard = null): ?\Illuminate\Contracts\Auth\Authenticatable
when(bool $condition, callable $true, ?callable $false = null): mixed
valueOrDefault(mixed $value = null, mixed $default = null, ...$args): mixed
db(?string $connection = null): \Illuminate\Database\ConnectionInterface
```

_Реализацию см. в коде проекта (поиск по `function user(` / `function when(`)._

**Правила использования:**
- Вместо `auth($guard)->user()` **предпочитай** `user($guard)`.
- Для ветвлений на уровне вызова используй `when(...)` (повышает читаемость компактных веток).
- Для "значение или дефолт (или коллбэк)" — `valueOrDefault(...)`.
- Для работы с БД за пределами Eloquent — `db()` (см. §4.11), **не** `DB::` фасад.

### 4.4 Доступ к элементам массивов (Arr::get по умолчанию)
Для **любой** работы с ассоциативными массивами, где ключ может отсутствовать, используем `Illuminate\Support\Arr::get(...)`
вместо прямого обращения и `??`:

**Good:**
```php
use Illuminate\Support\Arr;

$query = Arr::get($payload, 'q');
$limit = (int) Arr::get($payload, 'limit', 10);
```

**Bad:**
```php
$query = $payload['q'] ?? null;
$limit = (int) ($payload['limit'] ?? 10);
```

**Исключения (прямой доступ допустим):**
- массивы, которые **гарантированно** имеют ключи по контракту:
    - `$request->validated()` с `required` / `present` правилами;
    - локально сформированные массивы в **этом же** скоупе;
    - результат `array_merge`/`array_replace` с обязательными ключами;
    - итерация по списку заранее определённых ключей (константы, enum‑map);
- когда отсутствие ключа — **логическая ошибка**, и нужен явный `Undefined index` (например, строгие DTO‑shape).

### 4.5 Типы в сигнатурах: FQCN для вендорных классов
Во **всех** сигнатурах параметров/возвратов для классов/контрактов из фреймворка/вендоров — **полный FQCN inline**, без импортов ради сокращения.

**Good:**
```php
public function show(string $slug): \Illuminate\Contracts\Support\Responsable
{
    // ...
}
```

**Bad:**
```php
use Illuminate\Contracts\Support\Responsable;

// ❌ запрещено
public function show(string $slug): Responsable
{
    // ...
}
```

**Исключение:** наши собственные классы (`App\...`) можно импортировать и использовать короткие имена в сигнатурах. А так же уже написанный код.
Его сигнатуры можно менять разрешено по прямому запросу пользователя.

### 4.6 Исключения
Не импортировать глобальные исключения ради сокращения. Бросать/ловить через inline FQCN:

```php
throw new \Exception('...');

try {
    // ...
} catch (\Throwable $e) {
    // ...
}
```

### 4.7 Явные return‑type'ы и HTTP‑ответы
- **Все** публичные методы имеют явный return type.
- Экшены контроллеров возвращают один из:
    - `\Illuminate\Http\JsonResponse`
    - `\Illuminate\Http\RedirectResponse`
    - `\Symfony\Component\HttpFoundation\Response`
    - `\Inertia\Response`
    - `\Illuminate\Contracts\Support\Responsable`
- Если знаешь точный тип — **предпочитай конкретный** (например, `JsonResponse`), `Responsable` — когда действительно универсально.

### 4.8 Helpers > Facades (важно)
**MUST:** если у Laravel есть helper — используй **его**, фасад **нельзя**.

| Facade style                          | ✅ Используй helper                         |
|--------------------------------------|---------------------------------------------|
| `Auth::user()` / `Auth::guard()`     | `user()` или `auth()->user()` / `auth()`    |
| `Auth::check()`                      | `auth()->check()`                           |
| `DB::table('users')`                 | `db()->table('users')`                      |
| `DB::transaction(fn () => …)`        | `db()->transaction(fn () => …)`             |
| `Str::of('text')` / `Str::…`         | `str('text')` / `str()->…`                  |
| `Str::uuid()` / `Str::random()`      | `str()->uuid()` / `str()->random()`         |
| `Cache::get('k')` / `Cache::put()`   | `cache()->get('k')` / `cache()->put()`      |
| `Log::info('msg')`                   | `logger()->info('msg')`                     |
| `Response::json(...)`                | `response()->json(...)`                     |
| `Redirect::to(...)` / `route()`      | `redirect()->to(...)` / `redirect()->route()` |
| `Event::dispatch(new ...)`           | `event(new ...)`                            |
| `Bus::dispatch(new ...)`             | `dispatch(new ...)`                         |
| `Session::get()/put()`               | `session()->get()/put()`                    |
| `App::make(Foo::class)`              | `app(Foo::class)`                           |
| `URL::to(...)`                       | `url(...)`                                  |

`str()` без аргументов проксирует статические методы `Str::`, поэтому фасад не нужен.

> Там, где **нет** эквивалентного helper (например, часть `Storage::`), фасад допустим.

### 4.9 Контроллеры
- Контроллеры — тонкие: бизнес‑логика в сервисах/репозиториях.
- Валидация — через `FormRequest` (не inline `$request->validate()` при растущей логике).
- Возвраты через helpers (`redirect()`, `response()->json()`, `inertia()`), не фасады.
- Текущий пользователь — через `user()`/`auth()`.

### 4.10 Config / env / окружения
- Не читать `env()` в рантайме. Только `config()`.
- Для ветвления по окружению: `app()->environment('testing')`, `...('production')`, и т.п.
- Не хардкодить имена контейнеров/хосты `.env` в код — только конфиги.

### 4.11 Доступ к БД
- По умолчанию — **Eloquent**.
- Если нужны ручные запросы/транзакции — `db()` helper, не `DB::` фасад.
- Транзакции — выгодные для краткости кода: `db()->transaction(fn () => ...)` или `begin/commit/rollBack`.
- Сложные запросы — вне контроллеров (сервисы/репозитории) → тестируемость.

### 4.12 Ошибки и логирование
- Предпочитай фреймворковые механизмы: `abort(404)/abort_if($condition, 404)` или, при необходимости, фреймворковые исключения (например, `\Illuminate\Auth\Access\AuthorizationException`).
- Логируй через `logger()` **с контекстом**: `logger()->warning('Unexpected', ['id' => $id])`.

---

## 5) Порядок импортов (`use`)

**Группы и порядок:**
1. **Laravel**: `Illuminate\*`, `Laravel\*` (и при необходимости `Symfony\*`, относимый к экосистеме фреймворка).
2. **Сторонние библиотеки**: любой вендор, не входящий в Laravel (`Spatie\*`, `GuzzleHttp\*`, `Carbon\*`, и т.п.).
3. **Наши пространства имён `App\*`** — в таком под‑порядке:
4. **Enums и сервисные классы/инфраструктура**: `App\Enums\*`, `App\Services\*`, `App\Support\*`, `App\Actions\*`, `App\DTOs\*`.
5. **Абстракции/контракты и "слои" приложения**: `App\Http\Requests\*`, `App\Http\Resources\*`, `App\Contracts|Interfaces|Abstracts\*`, и т.п.
6. **Модели**: `App\Models\*`.

**Внутри каждой группы** — лексикографическая сортировка по FQCN.
Запрещены дубликаты и "двойные" `;;`.

**Пример (исходный список → отсортированный):**
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Enums\PageSliderFileType;
use App\Http\Requests\Dashboard\Page\PageRequest;
use App\Contracts\PaymentServiceContract;
use App\Models\PageSliderFile;
```

---

## 6) Summary / MUST / MUST NOT

### 6.1 MUST
- Каждый файл начинается с `declare(strict_types=1);` и следует PSR‑4.
- Все публичные методы имеют явные типы возврата; для HTTP — из перечисленных типов (§4.7).
- Для вендорных/фреймворковых типов в сигнатурах — inline FQCN (без импортов ради сокращения).
- Никогда не `use Exception;` — только `\Exception` / `\Throwable` inline.
- Helpers > Facades; где нет helper — фасад допустим (см. §4.8).
- Для массива с опциональными ключами — `Arr::get(...)` (см. §4.4).
- Пользовательские хелперы проекта: `user()` вместо `auth()->user()`, `when()`, `valueOrDefault()`, `db()` (§4.3).
- Контроллеры — тонкие; бизнес‑логика в сервисах; валидация — `FormRequest`.
- Конфигурация через `config()`, не `env()` в рантайме.
- Доступ к БД — Eloquent; при необходимости Query Builder — через `db()`. Транзакции — используем вариант для большей краткости.
- Контейнер/запуск команд: см. `.ai/guidelines/*core.md`.
- Тесты запускаются через `./vendor/bin/sail artisan test`, сериально по умолчанию.
- Порядок импортов — согласно §5 (Laravel → сторонние → `App\*`: Enums/Services → Requests/Data → Абстракции/Модели).

### 6.2 MUST NOT
- Не использовать `docker compose exec` напрямую — только через Sail.
- Не импортировать глобальные исключения ради сокращения имён.
- Не предлагать Pest `--parallel` без явного сигнала от пользователя.
- Логи/вывод: см. `.ai/guidelines/*core.md`.

---

## 7) Quick snippets

**Запуск Artisan‑команд:**
```bash
# Миграции
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed

# Генерация
./vendor/bin/sail artisan make:controller Api/UserController
./vendor/bin/sail artisan make:model Order -mfc
```

**Запуск тестов:**
```bash
# Все тесты
./vendor/bin/sail artisan test

# Конкретный файл
./vendor/bin/sail artisan test tests/Feature/UserTest.php

# По фильтру
./vendor/bin/sail artisan test --filter=test_user_can_login
```

**Composer:**
```bash
./vendor/bin/sail composer i
./vendor/bin/sail composer r spatie/laravel-data
```

**Фронтенд:**
```bash
./vendor/bin/sail bun i
./vendor/bin/sail bun run dev
./vendor/bin/sail bun run build
```
