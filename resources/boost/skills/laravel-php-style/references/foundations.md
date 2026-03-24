# Foundations

## `declare(strict_types=1);`

Каждый PHP-файл начинается так:

```php
<?php

declare(strict_types=1);

namespace App\...;
```

Правила:
- ровно 1 пустая строка после `declare`;
- затем `namespace`;
- один публичный класс на файл по умолчанию.

## Namespace / структура

- PSR-4 (`App\Http\Controllers\...`, `App\Services`, `App\Models`, ...).
- Один публичный класс на файл.

## Project helpers

В проекте доступны:

```php
user(?string $guard = null): ?\Illuminate\Contracts\Auth\Authenticatable
when(bool $condition, callable $true, ?callable $false = null): mixed
valueOrDefault(mixed $value = null, mixed $default = null, ...$args): mixed
db(?string $connection = null): \Illuminate\Database\ConnectionInterface
```

Правила:
- вместо `auth($guard)->user()` предпочитай `user($guard)`;
- для компактных ветвлений в выражениях используй `when(...)`;
- для "значение или дефолт" используй `valueOrDefault(...)`;
- для Query Builder/транзакций вне Eloquent используй `db()` вместо `DB::`.

## FQCN в сигнатурах

Если вендорный класс нужен в сигнатуре 1-2 раза, пиши inline FQCN:

```php
public function show(string $slug): \Illuminate\Contracts\Support\Responsable
{
    // ...
}
```

Наши `App\...` классы можно импортировать.

## Глобальные исключения

Глобальные исключения не импортируем ради сокращения:

```php
throw new \Exception('...');

try {
    // ...
} catch (\Throwable $e) {
    // ...
}
```

## Return types

- Все публичные методы имеют явный return type.
- Для HTTP по возможности предпочитай конкретные типы:
  - `\Illuminate\Http\JsonResponse`
  - `\Illuminate\Http\RedirectResponse`
  - `\Symfony\Component\HttpFoundation\Response`
  - `\Inertia\Response`
  - `\Illuminate\Contracts\Support\Responsable`

## Helpers > Facades

Если у Laravel есть helper, по умолчанию используем его:

| Facade style | Default helper style |
|---|---|
| `Auth::user()` / `Auth::guard()` | `user()` или `auth()->user()` / `auth()` |
| `Auth::check()` | `auth()->check()` |
| `DB::table('users')` | `db()->table('users')` |
| `DB::transaction(fn () => ...)` | `db()->transaction(fn () => ...)` |
| `Str::of('text')` / `Str::...` | `str('text')` / `str()->...` |
| `Str::uuid()` / `Str::random()` | `str()->uuid()` / `str()->random()` |
| `Cache::get('k')` / `Cache::put()` | `cache()->get('k')` / `cache()->put()` |
| `Log::info('msg')` | `logger()->info('msg')` |
| `Response::json(...)` | `response()->json(...)` |
| `Redirect::to(...)` / `route()` | `redirect()->to(...)` / `redirect()->route()` |
| `Event::dispatch(new ...)` | `event(new ...)` |
| `Bus::dispatch(new ...)` | `dispatch(new ...)` |
| `Session::get()/put()` | `session()->get()/put()` |
| `App::make(Foo::class)` | `app(Foo::class)` |
| `URL::to(...)` | `url(...)` |

Там, где helper-а нет (например, часть `Storage::`), facade допустим.

## Imports

Группы и порядок:
1. Laravel: `Illuminate\*`, `Laravel\*`, при необходимости `Symfony\*`
2. Third-party vendor imports
3. `App\*`

Внутри групп — лексикографическая сортировка по FQCN.

Отдельное правило:
- если один из импортов — родительский класс (`extends BaseClass`), он идёт первым среди всех `use`-импортов файла.

Пример:

```php
use App\Abstracts\BaseAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Enums\PageSliderFileType;
use App\Http\Requests\Dashboard\Page\PageRequest;
use App\Contracts\PaymentServiceContract;
use App\Models\PageSliderFile;
```
