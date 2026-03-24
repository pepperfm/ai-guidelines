# Strings And Enums

## `str()` / `Stringable`

Разделяй два сценария:
1. `str()->uuid()` / `str()->uuid7()` / `str()->orderedUuid()`
2. `str('...')->...` fluent chain -> `Illuminate\Support\Stringable`

Правила:
- UUID как строка -> `str()->uuid()->toString()`
- обычная PHP-строка из `Stringable` -> `->value()`
- не использовать `->toString()` как общий extractor для fluent `Stringable`

```php
$uuid = str()->uuid()->toString();
$ordered = str()->orderedUuid()->toString();

$slug = str($title)->squish()->slug()->value();
$name = str($payloadName)->trim()->value();
```

## Интерполяция строк

Интерполяция разрешена широко. Не упрощай читаемую интерполяцию во временные переменные или конкатенацию без причины.

По умолчанию:
- простая переменная -> без `{}`;
- простое свойство объекта -> без `{}`;
- сложное выражение -> прямо в строке через `{...}`.

```php
"Hello, $name"
"User: $user->email"
"$in\n$out"
"case-$i"

"User: {$service->resolveUserName($user)}"
"Limit: {$payload['limit']}"
"Title: {$post?->author?->profile?->display_name}"
```

Фигурные скобки точно нужны для:
- массивного доступа
- вызова метода
- null-safe / длинной цепочки
- синтаксической двусмысленности вроде `"{$name}_id"`

## Enums

Главный принцип:
- для фиксированных перечислений почти всегда используем enum;
- строковые литералы для status/type/mode/role/state не держим, если enum уже есть;
- внутри домена работаем с enum case, а не со строковым `->value`.

```php
if ($status === Status::Draft) {
    // ...
}
```

`->value` используем на границе:
- payload
- json
- внешний API
- storage / serialization

```php
$payload['status'] = $status->value;
```

Не по умолчанию:

```php
if ($status->value === 'draft') {
    // ...
}
```

Если подключён `pepperfm/macros-for-laravel`, значения из массива превращаем в enum через `Arr::toEnum(...)`:

```php
$status = Arr::toEnum($payload, 'status', Status::class);
```

Для display-logic предпочитаем методы на enum, например `getLabel()`.

`array<Status>` и `Collection<Status>` — нормальная форма, не нужно преждевременно сводить всё к scalar values.
