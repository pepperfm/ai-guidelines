# Data Access

## Direct access / `Arr::get()` / `data_get()`

Главный принцип:
- guaranteed key -> прямой доступ;
- optional array key -> `Arr::get(...)`;
- mixed/object path -> `data_get(...)`.

```php
$email = $validated['email'];
$query = Arr::get($payload, 'q');
$name = Arr::get($payload, 'user.profile.name');
$title = data_get($item, 'meta.title');
```

Плохо по умолчанию:

```php
$query = $payload['q'] ?? null;
$title = $payload['meta']['title'] ?? null;
```

Если подключён `pepperfm/macros-for-laravel`, soft-cast делаем через `Arr::*` macros. Подробности см. в `laravel-array-macros`.

## `blank()` / `filled()` / `null` / `empty()`

По умолчанию:
- "пусто / заполнено" -> `blank()` / `filled()`;
- именно `null` -> `=== null` / `!== null`;
- `empty()` в основном допустим для явной проверки пустого массива.

```php
if (blank($title)) {
    return;
}

if (filled($payloadName)) {
    // ...
}

if ($user === null) {
    // ...
}

if (empty($items)) {
    // array is empty
}
```

## `Collection` vs `array`

Главный принцип:
- fluent transformations -> `Collection`;
- boundary / contract / native PHP -> `array`;
- не прыгай `Collection -> array -> Collection` без причины.

```php
$emails = $users->pluck('email')->filter()->values();

$names = collect($payload)
    ->pluck('name')
    ->filter()
    ->values();

return $names->all(); // if contract is array
```

Нормальный переход в массив:

```php
$names = $users->pluck('name')->filter()->values()->all();

sort($names);

return $names;
```

## Eloquent access patterns

Предпочтения по умолчанию:
- наличие записей -> `->exists()`
- отсутствие записей -> `->doesntExist()`
- один scalar из запроса -> `->value(...)`
- одна nullable-модель -> `->first()` / `->find()`
- одна обязательная модель -> `->firstOrFail()` / `->findOrFail()`
- одна запись по условию -> `->firstWhere(...)`
- список значений с переходом в массив -> `->pluck(...)->all()`

```php
if (User::query()->where('email', $email)->doesntExist()) {
    // ...
}

$email = User::query()->whereKey($id)->value('email');

$user = User::query()->firstWhere('email', $email);

$post = Post::query()->findOrFail($id);

$ids = User::query()->pluck('id')->all();
```

Альтернативы не запрещены жёстко, но default именно такой.
