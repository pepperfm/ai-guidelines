---
name: laravel-php-style
description: 'Стиль PHP/Laravel в проекте: strict_types, helpers, Arr/data_get, Stringable/str(), Eloquent defaults, request/validation, HTTP helpers, naming, enums, imports. Активируй при работе с backend-кодом.'
---

# Skill: Laravel — PHP & Laravel Code Style

**Версия:** 2026-03-25

## Когда использовать

- Пишешь или рефакторишь PHP/Laravel-код.
- Нужны правила по helpers, массивам, `Stringable`, Eloquent, контроллерам, request/validation, enum, импортам.
- Нужно быстро понять не "как можно", а "как писать по проектному default style".

## Как использовать этот skill

Сначала прочитай этот файл целиком. Затем открой только один релевантный `references/*.md`, если не нужна комбинация тем.

- `references/foundations.md`
  Use when deciding about base PHP/Laravel structure: `strict_types`, namespace, project helpers, FQCN в сигнатурах, return types, imports.
- `references/data-access.md`
  Use when deciding about data reads and access style: direct access vs `Arr::get(...)` vs `data_get(...)`, array-macros, `blank()/filled()`, `Collection`, Eloquent access pattern.
- `references/strings-and-enums.md`
  Use when deciding about strings and enums: `str()`, `Stringable`, интерполяция, enum cases, `->value`, labels, `Arr::toEnum(...)`.
- `references/http-and-errors.md`
  Use when deciding about HTTP layer and failures: controller responses, redirect helpers, `abort_if(...)`, domain exceptions, logging/reporting.
- `references/requests-and-naming.md`
  Use when deciding about request-layer and local readability: `Request` vs `FormRequest`, data-like request objects, inline validation, temp variables, naming, needless refactors.

Do not open extra references "на всякий случай". Если вопрос локальный, достаточно одного файла.

## Core Defaults

- Каждый PHP-файл начинается с `declare(strict_types=1);`.
- Helper-first: если у Laravel есть helper, по умолчанию используем его, а не facade.
- Guaranteed key -> прямой доступ; optional array key -> `Arr::get(...)`; mixed/object path -> `data_get(...)`.
- Если подключён `pepperfm/macros-for-laravel`, soft-cast из массива делаем через `Arr::*` macros, а не через `(type) Arr::get(...)`.
- UUID string получаем через `str()->uuid()->toString()`. Обычную PHP-строку из fluent `Stringable` получаем через `->value()`.
- Простые `$var` и `$object->property` интерполируем без `{}`. Более сложные выражения оставляем прямо в строке через `{...}` и не упрощаем без причины.
- Для "пусто / заполнено" по умолчанию используем `blank()` / `filled()`. `empty()` в основном допустим для явной проверки пустого массива.
- `Collection` держим для fluent-обработки. В `array` переходим только на границе контракта, native PHP или внешнего payload.
- В Eloquent по умолчанию предпочитаем `exists()/doesntExist()`, `value()`, `firstWhere()`, `findOrFail()/firstOrFail()`, `pluck(...)->all()`.
- В контроллерах helper-first и sugar-first: `response()->json(...)`, `to_route(...)`, `back()`. Domain layer по умолчанию бросает исключения.
- Request-like DI object по умолчанию называется `$request`. Переменная исключения всегда `$e`.
- Не вводим временные переменные и локальные рефакторы без реального выигрыша в читаемости.
- Для фиксированных перечислений почти всегда используем enum. Сравнение по case, `->value` только на границе, labels через методы enum.
- Если импортирован родительский класс (`extends BaseClass`), он идёт первым среди всех `use`-импортов файла.

## Быстрые примеры

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Abstracts\BaseAction;
use Illuminate\Support\Arr;

$email = $validated['email'];
$title = Arr::get($payload, 'meta.title');
$name = data_get($item, 'author.profile.name');

$uuid = str()->uuid()->toString();
$slug = str($title)->squish()->slug()->value();

if (blank($title)) {
    return to_route('posts.index');
}

return response()->json([
    'status' => $status->value,
]);
```

## Anti-Patterns

- Не писать `$payload['x'] ?? null` как default style вместо `Arr::get(...)`.
- Не писать `(int) Arr::get(...)`, `(bool) Arr::get(...)`, `trim((string) Arr::get(...))`, если есть `laravel-array-macros`.
- Не упрощать сложную, но читаемую интерполяцию во временные переменные или конкатенацию без причины.
- Не тащить `->value` из enum внутрь доменной логики без необходимости.
- Не логировать и не `report(...)` исключение перед пробросом без новой полезной информации.
- Не переименовывать request-like DI объект в `$data`, `$dto`, `$payload`, если он играет роль request-контракта.

## Rule Of Thumb

Если выбор сводится к "более коротко, но не теряя смысла" vs "более многословно ради мнимой аккуратности", по умолчанию выбираем более короткий и локально ясный вариант.
