# Requests And Naming

## Request / validation / FormRequest / data objects

Главный принцип:
- request layer может быть и обычным `Request`, и `FormRequest`, и data-like object;
- если объект пришёл через DI как request-contract, переменная по умолчанию называется `$request`;
- не усложнять input layer без причины, но если логика растёт, default move — в `FormRequest`.

Допустимо:

```php
public function store(Request $request): \Illuminate\Http\RedirectResponse
{
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'name' => ['required', 'string'],
    ]);
}
```

```php
public function update(UpdateUserRequest $request): \Illuminate\Http\JsonResponse
{
    // ...
}
```

```php
public function sync(UpdateUserData $request): \Illuminate\Http\JsonResponse
{
    // data-like request object, but variable is still $request
}
```

По умолчанию:
- простой input -> обычный `Request` допустим;
- немного простых правил -> inline `$request->validate(...)` допустим;
- если слой растёт -> `FormRequest`;
- если нужен тип -> typed request method;
- если специальный тип не нужен -> `$request->input()` достаточно.

```php
$email = $request->string('email')->value();
$isActive = $request->boolean('is_active');
$page = $request->integer('page');

$search = $request->input('search');
```

## Naming / temp variables / needless refactors

Главный принцип:
- не вводить временную переменную без реальной пользы;
- не дробить локально ясный код на большее число строк без выигрыша в читаемости;
- имена должны быть достаточно конкретными для текущего контекста, но без искусственного переусложнения.

Если значение используется один раз и выражение остаётся читаемым, не выноси его в отдельную переменную:

```php
return $users->pluck('email')->filter()->values()->all();

if (blank($title)) {
    return;
}
```

Не по умолчанию:

```php
$emails = $users->pluck('email')->filter()->values()->all();

return $emails;
```

```php
$isBlankTitle = blank($title);

if ($isBlankTitle) {
    return;
}
```

Имена вроде `$result`, `$data`, `$item`, `$items`, `$payload` допустимы, если:
- кейс простой;
- переменных мало;
- нет двусмысленности;
- из ближайшего контекста сразу понятно значение.

В сложном контексте имя должно стать конкретнее.

Нормально:

```php
$user
$post
$name
$payload
$items
```

Слишком абстрактно в сложном контексте:

```php
$result
$data
```

если реально речь о чём-то конкретном вроде `invoice payload`, `sync result`, `user profile slug`.

Дополнительно:
- не вводить переменную только ради следующего `return`;
- не вводить переменную только ради следующего `if`, если условие и так читаемо;
- bool-переменные называть по смыслу (`is...`, `has...`, `can...`, `should...`) там, где это помогает.
