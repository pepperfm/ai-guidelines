---
name: laravel-php-style
description: 'Стиль PHP/Laravel в проекте: strict_types, helpers, Arr::get, FQCN, импорты, типы HTTP-ответов. Активируй при работе с backend-кодом.'
---

# Skill: Laravel — PHP & Laravel Code Style

**Версия:** 2026‑01‑30

**Когда использовать:**
- Пишешь/рефакторишь PHP-код (контроллеры, сервисы, модели, DTO, консольные команды).
- Нужно понять, какие helpers использовать вместо фасадов.
- Нужно правило `Arr::get` для опциональных ключей.
- Нужно правило про FQCN в сигнатурах.

> Эта skill — «детальная часть» пресета Laravel. Краткие MUST лежат в `.ai/guidelines/10-laravel.md`.

---

## 1) `declare(strict_types=1);` (MUST)

Каждый PHP‑файл **начинается** так:

```php
<?php

declare(strict_types=1);

namespace App\...;
```

Правила:
- ровно **1** пустая строка после `declare`;
- затем `namespace`;
- «1 публичный класс на файл» — по умолчанию.

---

## 2) Namespace / структура классов

- PSR‑4 (`App\Http\Controllers\...`, `App\Services`, `App\Models`, …).
- Один публичный класс на файл.

---

## 3) Проектные хелперы (использовать по умолчанию)

В проекте доступны:

```php
user(?string $guard = null): ?\Illuminate\Contracts\Auth\Authenticatable
when(bool $condition, callable $true, ?callable $false = null): mixed
valueOrDefault(mixed $value = null, mixed $default = null, ...$args): mixed
db(?string $connection = null): \Illuminate\Database\ConnectionInterface
```

Правила:
- вместо `auth($guard)->user()` **предпочитай** `user($guard)`;
- для компактных ветвлений в выражениях — `when(...)`;
- для «значение или дефолт (в т.ч. коллбэк)» — `valueOrDefault(...)`;
- для Query Builder/транзакций вне Eloquent — `db()` вместо `DB::`.

_Реализацию см. в коде проекта (поиск по `function user(` / `function when(`)._ 

---

## 4) Доступ к элементам массивов (Arr::get по умолчанию)

Для **любой** работы с ассоциативными массивами, где ключ может отсутствовать, используем `Illuminate\Support\Arr::get(...)`.

✅ Good:
```php
use Illuminate\Support\Arr;

$query = Arr::get($payload, 'q');
$limit = (int) Arr::get($payload, 'limit', 10);
```

❌ Bad:
```php
$query = $payload['q'] ?? null;
$limit = (int) ($payload['limit'] ?? 10);
```

Исключения (прямой доступ допустим):
- массивы, где ключи **гарантированы контрактом**:
  - `$request->validated()` с `required` / `present` правилами;
  - локально сформированные массивы в **этом же** скоупе;
  - результат `array_merge`/`array_replace` с обязательными ключами;
  - итерация по заранее определённым ключам (константы, enum‑map);
- когда отсутствие ключа — **логическая ошибка** и нужен явный `Undefined index`.

---

## 5) Типы в сигнатурах: FQCN для вендорных классов (MUST)

Если сигнатура класса, который нужен для типизации параметров/возвратов для классов/контрактов из фреймворка/вендоров, используется в классе 1-2 раза — пиши inline **полный FQCN inline**, без импортов ради сокращения.

✅ Good:
```php
public function show(string $slug): \Illuminate\Contracts\Support\Responsable
{
    // ...
}
```

❌ Bad:
```php
use Illuminate\Contracts\Support\Responsable;

public function show(string $slug): Responsable
{
    // ...
}
```

Исключение: **наши** классы (`App\...`) можно импортировать.

---

## 6) Исключения (MUST)

Не импортировать глобальные исключения ради сокращения:

```php
throw new \Exception('...');

try {
    // ...
} catch (\Throwable $e) {
    // ...
}
```

---

## 7) Явные return‑type'ы и HTTP‑ответы (MUST)

- **Все** публичные методы имеют явный return type.
- Экшены контроллеров возвращают один из:
  - `\Illuminate\Http\JsonResponse`
  - `\Illuminate\Http\RedirectResponse`
  - `\Symfony\Component\HttpFoundation\Response`
  - `\Inertia\Response`
  - `\Illuminate\Contracts\Support\Responsable`
- Если знаешь точный тип — **предпочитай конкретный** (например, `JsonResponse`).

---

## 8) Helpers > Facades (MUST)

Если у Laravel есть helper — используй **его**, фасад **нельзя**.

| Facade style                          | ✅ Используй helper                                   |
|--------------------------------------|------------------------------------------------------|
| `Auth::user()` / `Auth::guard()`     | `user()` или `auth()->user()` / `auth()`            |
| `Auth::check()`                      | `auth()->check()`                                    |
| `DB::table('users')`                 | `db()->table('users')`                               |
| `DB::transaction(fn () => …)`        | `db()->transaction(fn () => …)`                      |
| `Str::of('text')` / `Str::…`         | `str('text')` / `str()->…`                           |
| `Str::uuid()` / `Str::random()`      | `str()->uuid()` / `str()->random()`                  |
| `Cache::get('k')` / `Cache::put()`   | `cache()->get('k')` / `cache()->put()`               |
| `Log::info('msg')`                   | `logger()->info('msg')`                              |
| `Response::json(...)`                | `response()->json(...)`                              |
| `Redirect::to(...)` / `route()`      | `redirect()->to(...)` / `redirect()->route()`        |
| `Event::dispatch(new ...)`           | `event(new ...)`                                     |
| `Bus::dispatch(new ...)`             | `dispatch(new ...)`                                  |
| `Session::get()/put()`               | `session()->get()/put()`                             |
| `App::make(Foo::class)`              | `app(Foo::class)`                                    |
| `URL::to(...)`                       | `url(...)`                                           |

`str()` без аргументов проксирует статические методы `Str::`, поэтому фасад не нужен.

> Там, где **нет** эквивалентного helper (например, часть `Storage::`) — фасад обязателен.

---

## 9) Контроллеры

- Контроллеры — **тонкие**: бизнес‑логика в сервисах/репозиториях.
- Валидация — через `FormRequest` (не inline `$request->validate()` при растущей логике).
- Возвраты через helpers (`redirect()`, `response()->json()`, `inertia()`), не фасады.
- Текущий пользователь — через `user()`/`auth()`.

---

## 10) Config / env / окружения

- Не читать `env()` в рантайме. Только `config()`.
- Для ветвления по окружению: `app()->isLocal()`, `app()->environment('testing')`, `...('production')`, и т.п.
- Не хардкодить имена контейнеров/хосты `.env` в код — только конфиги.

---

## 11) Доступ к БД

- По умолчанию — **Eloquent**.
- Если нужны ручные запросы/транзакции — `db()` helper, не `DB::` фасад.
- Транзакции: `db()->transaction(fn () => ...)` либо `begin/commit/rollBack`.
- Сложные запросы — вне контроллеров (сервисы/репозитории) → тестируемость.

---

## 12) Ошибки и логирование

- Предпочитай фреймворковые механизмы: `abort(404)` / `abort_if(...)`.
- Логирование — через `logger()` **с контекстом**:

```php
logger()->warning('Unexpected state', ['id' => $id]);
```

---

## 13) Порядок импортов (`use`)

Группы и порядок:
1. **Laravel**: `Illuminate\*`, `Laravel\*` (и при необходимости `Symfony\*`, относимый к экосистеме фреймворка).
2. **Сторонние библиотеки**: любой вендор, не входящий в Laravel (`Spatie\*`, `GuzzleHttp\*`, `Carbon\*`, …).
3. **Наши `App\*`** — в таком под‑порядке:
   - `App\Enums\*`, `App\Services\*`, `App\Support\*`, `App\Actions\*`, `App\DTOs\*`.
   - Requests/Resources/Contracts/Abstracts (слои приложения).
   - `App\Models\*`.

Внутри каждой группы — лексикографическая сортировка по FQCN.

Пример:
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Enums\PageSliderFileType;
use App\Http\Requests\Dashboard\Page\PageRequest;
use App\Contracts\PaymentServiceContract;
use App\Models\PageSliderFile;
```
