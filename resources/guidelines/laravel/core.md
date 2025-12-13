# Codex — Laravel/Sail/MCP Guidelines (Personal Overrides)

**Version:** 2025‑11‑13

This document defines how Codex must behave in our Laravel/Sail projects.

You are expected to:
- generate production‑quality Laravel/PHP code that matches this style guide,
- interact with the running Sail container **only** through MCP tools,
- run tests through MCP, not through host Docker or direct `phpunit`,
- respect project constraints like PHP version (8.3 vs 8.4) and DB permissions,
- avoid suggesting steps that break sandbox or require Docker socket access.

> **Precedence**
> - **MUST** > SHOULD. In conflicts, rules with MUST win.
> - This document **overrides** generic Laravel docs, tutorials and defaults.
> - If Codex loads multiple instruction files, the one **ближе к рабочей директории** имеет больший приоритет; при прямом конфликте следуй этому документу.

## Language & Tone (MUST)

- Основной язык общения: **Русский**.
- Все пояснения на естественном языке (ответы, пошаговые инструкции, статусы задач, резюме ошибок и тестов) агент **MUST** давать **на русском**.
- Код, имена классов/переменных/файлов, ключи `.env`, текст CLI/исключений — **не переводить**; приводить как есть. Сначала краткое русское резюме, затем цитата оригинала.
- Если пользователь **явно** попросил другой язык — переключиться на него **только для этого ответа**; по умолчанию — русский.
- Длинные логи/трейсы на английском: сначала краткое русское резюме, затем “хвост” лога (см. лимиты вывода в этом документе).
- Это правило **перекрывает** общие настройки/дефолты и руководства, если есть противоречие.

**Assumptions:**
- Laravel 12+ style,
- strict types,
- Pest tests,
- Sail for local dev,
- MCP server exposed via `boost:mcp`,
- per‑project Artisan command `boost:run-tests`,
- project helpers available: `user()`, `when()`, `valueOrDefault()`, `db()` (см. §4.3).

---

## 1) Agent Behavior / Boundaries

### 1.1 Never assume direct Docker access
Codex работает в песочнице и **не может** надёжно:
- вызывать `docker compose exec ...`,
- читать/маунтить `/var/run/docker.sock`,
- запускать Sail непосредственно с хоста,
- полагаться на версию PHP на хосте.

Значит:
- **НЕ пиши** “I'll run `docker compose exec app php artisan ...`”,
- **НЕ проси** доступ к docker.sock или “выключить песочницу”,
- **НЕ утверждай**, что запускал `php artisan` локально вне контейнера.

Вместо этого **всё выполнение** — только через MCP‑инструменты проекта.

### 1.2 Use MCP tools to act inside the app (tools contract)
**Минимально ожидаемые инструменты:**

- **`tinker`**  
  _Input:_ строка PHP‑кода (UTF‑8).  
  _Exec env:_ внутри Sail‑контейнера проекта.  
  _Output:_ строка (`return` последнего выражения или собранный вывод — см. примеры).  
  _Purpose:_ любые инлайн‑вызовы `Artisan::call(...)`, отладка, чтение состояния приложения.

- **`boost:run-tests` (через Artisan)**  
  Запускается **через `tinker`**, см. §2.2 и §3.2. Возвращает **строго** JSON (см. схему ниже).

**Таймауты/вывод:**
- Стандартный запуск одной команды: **до 60 с**; для тестов/миграций допускается до **180 с**.
- Если stdout > **64 KiB** или > **200 строк**, агент **MUST**:
    1) дать краткое резюме,
    2) приложить “хвост” (последние ~100 строк) в свернутом блоке,
    3) предложить полный лог по запросу.  
       Агент **не должен** заливать в ответ много сотен строк лога.

**Фоллбек:** если конкретный MCP‑инструмент **недоступен/падает**, агент говорит:
> “I can’t execute this inside the container via MCP in this project. Please run `php artisan <command>` manually inside Sail.”  
и **не** просит никаких привилегий Docker.

---

## 2) Running Artisan via MCP

### 2.1 Базовый шаблон
Никогда не проси пользователя запускать `docker compose exec …` от имени агента. Любая Artisan‑команда исполняется через `tinker` так:

```php
<?php

use Illuminate\Support\Facades\Artisan;

Artisan::call('some:command', [
    // 'option' => 'value',
]);

return Artisan::output();
```

Агент отправляет этот сниппет в MCP:`tinker` и получает строковый результат.

### 2.2 Алгоритм действий (минимальное дерево решений)
1. Нужна команда Artisan → **попробуй MCP:`tinker`**.
2. Если MCP отсутствует/падает → выдай фразу‑фоллбек (см. выше).
3. Никогда не проси docker.sock/привилегии/выход из песочницы.

---

## 3) Testing

### 3.1 Реальность окружений
Проекты различаются: версия PHP (8.3/8.4), настройки БД/права, имена сервисов.  
Codex **не полагается** на хостовый PHP, **не** исполняет Sail напрямую и **не** просит docker.sock.

### 3.2 Как тесты ДОЛЖНЫ запускаться
Каждый проект предоставляет Artisan‑команду `boost:run-tests`, которая:
- принудительно устанавливает `APP_ENV=testing`,
- запускает Pest/`php artisan test` **внутри контейнера**,
- выполняет **сериално** (по умолчанию **без** `--parallel`),
- возвращает **одну JSON‑строку** со строгой схемой:

```json
{
  "ok": true,
  "exitCode": 0,
  "summary": {
    "tests": 123,
    "assertions": 456,
    "failures": 0,
    "errors": 0,
    "skipped": 0,
    "timeSec": 12.34
  },
  "stdout": "...",
  "stderr": ""
}
```

**Семантика:**
- `ok = true` ⇔ `exitCode === 0` и `summary.failures === 0` и `summary.errors === 0`.
- Если вывод **невалидный JSON** → считать прогон **неуспешным**, показать сырой вывод и предложить повтор.
- Агент **MUST** парсить JSON и кратко резюмировать результаты/пайплайны провалов.

**Стандартный вызов через MCP:**
```php
<?php

use Illuminate\Support\Facades\Artisan;

Artisan::call('boost:run-tests');

return Artisan::output(); // JSON-строка по схеме выше
```

### 3.3 Параллельные тесты
По умолчанию **запрещено** предлагать `--parallel`. Разрешено **только** если пользователь **явно** сообщил, что настроены права БД для воркеров (иначе вероятна `SQLSTATE[HY000] [1044] Access denied`).

### 3.4 После изменений кода
После генерации/рефакторинга агент **SHOULD** предложить прогнать `boost:run-tests` через MCP и выдать краткое резюме/хвост логов.

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
<?php

if (!function_exists('user')) {
    /**
     * @param ?string $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    function user(?string $guard = null): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return auth($guard)->user();
    }
}

if (!function_exists('when')) {
    function when(bool $condition, callable $true, ?callable $false = null)
    {
        if ($condition) {
            return $true();
        }
        if ($false) {
            return $false();
        }

        return null;
    }
}

if (!function_exists('valueOrDefault')) {
    function valueOrDefault(mixed $value = null, mixed $default = null, ...$args)
    {
        if (blank($args) && blank($value)) {
            return $default;
        }

        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('db')) {
    function db(?string $connection = null): \Illuminate\Database\ConnectionInterface
    {
        return app('db')->connection($connection);
    }
}
```

**Правила использования:**
- Вместо `auth($guard)->user()` **предпочитай** `user($guard)`.
- Для ветвлений на уровне вызова используй `when(...)` (повышает читаемость компактных веток).
- Для “значение или дефолт (или коллбэк)” — `valueOrDefault(...)`.
- Для работы с БД за пределами Eloquent — `db()` (см. §4.7), **не** `DB::` фасад.

### 4.4 Типы в сигнатурах: FQCN для вендорных классов
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

**Исключение:** наши собственные классы (`App\...`) можно импортировать и использовать короткие имена в сигнатурах.

### 4.5 Исключения
Не импортировать глобальные исключения ради сокращения. Бросать/ловить через inline FQCN:

```php
throw new \Exception('...');

try {
    // ...
} catch (\Throwable $e) {
    // ...
}
```

### 4.6 Явные return‑type’ы и HTTP‑ответы
- **Все** публичные методы имеют явный return type.
- Экшены контроллеров возвращают один из:
    - `\Illuminate\Http\JsonResponse`
    - `\Illuminate\Http\RedirectResponse`
    - `\Symfony\Component\HttpFoundation\Response`
    - `\Inertia\Response`
    - `\Illuminate\Contracts\Support\Responsable`
- Если знаешь точный тип — **предпочитай конкретный** (например, `JsonResponse`), `Responsable` — когда действительно универсально.

### 4.7 Helpers > Facades (важно)
Если у Laravel есть helper — используй **его**, а не фасад.

| Facade style                          | ✅ Используй helper                         |
|--------------------------------------|---------------------------------------------|
| `Auth::user()` / `Auth::guard()`     | `user()` или `auth()->user()` / `auth()`    |
| `Auth::check()`                      | `auth()->check()`                           |
| `DB::table('users')`                 | `db()->table('users')`                      |
| `DB::transaction(fn () => …)`        | `db()->transaction(fn () => …)`             |
| `Str::of('text')` / `Str::…`         | `str('text')` / `str()->…`                  |
| `Cache::get('k')` / `Cache::put()`   | `cache()->get('k')` / `cache()->put()`      |
| `Log::info('msg')`                   | `logger()->info('msg')`                     |
| `Response::json(...)`                | `response()->json(...)`                     |
| `Redirect::to(...)` / `route()`      | `redirect()->to(...)` / `redirect()->route()` |
| `Event::dispatch(new ...)`           | `event(new ...)`                            |
| `Bus::dispatch(new ...)`             | `dispatch(new ...)`                         |
| `Session::get()/put()`               | `session()->get()/put()`                    |
| `App::make(Foo::class)`              | `app(Foo::class)`                           |
| `URL::to(...)`                       | `url(...)`                                  |

> Там, где **нет** эквивалентного helper (например, часть `Storage::`), фасад допустим.

### 4.8 Контроллеры
- Контроллеры — тонкие: бизнес‑логика в сервисах/репозиториях.
- Валидация — через `FormRequest` (не inline `$request->validate()` при растущей логике).
- Возвраты через helpers (`redirect()`, `response()->json()`, `inertia()`), не фасады.
- Текущий пользователь — через `user()`/`auth()`.

### 4.9 Config / env / окружения
- Не читать `env()` в рантайме. Только `config()`.
- Для ветвления по окружению: `app()->environment('testing')`, `...('production')`, и т.п.
- Не хардкодить имена контейнеров/хосты `.env` в код — только конфиги.

### 4.10 Доступ к БД
- По умолчанию — **Eloquent**.
- Если нужны ручные запросы/транзакции — `db()` helper, не `DB::` фасад.
- Транзакции — только `db()->transaction(fn () => ...)`, не ручные `begin/commit/rollBack`.
- Сложные запросы — вне контроллеров (сервисы/репозитории) → тестируемость.

### 4.11 Ошибки и логирование
- Предпочитай фреймворковые механизмы: `abort(404)` или, при необходимости, фреймворковые исключения (например, `\Illuminate\Auth\Access\AuthorizationException`).
- Логируй через `logger()` **с контекстом**: `logger()->warning('Unexpected', ['id' => $id])`.

---

## 5) Порядок импортов (`use`)

**Группы и порядок:**
1. **Laravel**: `Illuminate\*`, `Laravel\*` (и при необходимости `Symfony\*`, относимый к экосистеме фреймворка).
2. **Сторонние библиотеки**: любой вендор, не входящий в Laravel (`Spatie\*`, `GuzzleHttp\*`, `Carbon\*`, и т.п.).
3. **Наши пространства имён `App\*`** — в таком под‑порядке:
    1. **Enums и сервисные классы/инфраструктура**: `App\Enums\*`, `App\Services\*`, `App\Support\*`, `App\Actions\*`, `App\DTOs\*`.
    2. **Абстракции/контракты и “слои” приложения**: `App\Contracts|Interfaces|Abstracts\*`, `App\Http\Requests\*`, `App\Http\Resources\*`, и т.п.
    3. **Модели**: `App\Models\*`.

**Внутри каждой группы** — лексикографическая сортировка по FQCN.  
**Ровно одна пустая строка** между группами, **без** пустых строк внутри группы.  
Запрещены дубликаты и “двойные” `;;`.

**Пример (исходный список → отсортированный):**
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

use App\Enums\PageSliderFileType;
use App\Http\Requests\Dashboard\Page\PageRequest;
use App\Http\Requests\Dashboard\Page\PageStoreRequest;
use App\Http\Requests\Dashboard\Page\PageUpdateRequest;
use App\Models\Dance;
use App\Models\Page;
use App\Models\PageSliderFile;
```

---

## 6) Summary / MUST / MUST NOT

### 6.1 MUST
- Каждый файл начинается с `declare(strict_types=1);` и следует PSR‑4.
- Все публичные методы имеют явные типы возврата; для HTTP — из перечисленных типов (§4.6).
- Для вендорных/фреймворковых типов в сигнатурах — inline FQCN (без импортов ради сокращения).
- Никогда не `use Exception;` — только `\Exception` / `\Throwable` inline.
- Helpers > Facades; где нет helper — фасад допустим (см. §4.7).
- Пользовательские хелперы проекта: `user()` вместо `auth()->user()`, `when()`, `valueOrDefault()`, `db()` (§4.3).
- Контроллеры — тонкие; бизнес‑логика в сервисах; валидация — `FormRequest`.
- Конфигурация через `config()`, не `env()` в рантайме.
- Доступ к БД — Eloquent; при необходимости Query Builder — через `db()`. Транзакции — только `db()->transaction()`.
- Взаимодействие с приложением — **только** через MCP‑инструменты внутри контейнера.
- Тесты — **только** `boost:run-tests` через MCP:`tinker`, сериално по умолчанию. Агент парсит JSON‑схему и кратко резюмирует результат.
- Порядок импортов — согласно §5 (Laravel → сторонние → `App\*`: Enums/Services → Абстракции/Requests → Модели).

### 6.2 MUST NOT
- Никаких просьб о docker.sock/привилегиях/отключении песочницы.
- Не запускать `docker compose exec ...` и не просить пользователя сделать это “за агента”.
- Не утверждать, что агент запускал `php artisan test` локально вне контейнера.
- Не импортировать глобальные исключения ради сокращения имён.
- Не предлагать Pest `--parallel` без явного сигнала от пользователя.
- Не засорять ответы длинными логами — резюмировать и прикладывать хвост.

---

## 7) Quick snippets

**Artisan через MCP (`tinker`):**
```php
<?php

use Illuminate\Support\Facades\Artisan;

Artisan::call('migrate', ['--force' => true]);

return Artisan::output();
```

**Запуск тестов:**
```php
<?php

use Illuminate\Support\Facades\Artisan;

Artisan::call('boost:run-tests');

return Artisan::output(); // JSON по §3.2
```
