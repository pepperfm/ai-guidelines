# HTTP And Errors

## Controllers

- Контроллеры тонкие.
- Растущую бизнес-логику выносим из контроллера.
- Текущий пользователь получаем через `user()` / `auth()`.

## Config / env

- Не читать `env()` в runtime. Только `config()`.
- Для environment checks использовать `app()->isLocal()`, `app()->environment(...)`.
- Не хардкодить `.env` hosts/keys в коде.

## DB access

- По умолчанию — Eloquent.
- Для ручных запросов/транзакций использовать `db()` helper, не `DB::`.
- Сложные запросы держать вне контроллеров.

## HTTP responses / redirects / aborts

Главный принцип:
- в контроллере по умолчанию возвращаем готовый HTTP response через helper;
- в domain layer по умолчанию бросаем исключения, а не строим HTTP responses;
- sugar helpers в приоритете, если выражают намерение короче.

По умолчанию:
- JSON -> `response()->json(...)`
- redirect to route -> `to_route(...)`
- redirect back -> `back()`
- короткое guard-condition -> `abort_if(...)` / `abort_unless(...)`

```php
return response()->json($data);
return to_route('users.index');
return back();
return inertia('Users/Index', $props);
return view('users.index', $data);
```

Return types:
- если тип ясен, предпочитай конкретный type;
- union type допустим, если метод реально может вернуть несколько корректных HTTP-типов.

```php
public function index(): \Inertia\Response
public function store(): \Illuminate\Http\RedirectResponse
public function show(): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
```

Не по умолчанию:
- `new JsonResponse(...)`, если хватает `response()->json(...)`
- `redirect()->route(...)`, если хватает `to_route(...)`
- `redirect()->back()`, если хватает `back()`

## Errors / logging

- Для исключений имя переменной всегда `$e`.
- Helpers `abort_if`, `throw_if` используем там, где условие реально короче и чище.
- Если обычный `if (...) { throw ... }` читабельнее, не насилуем helper.

Логи можно писать и строкой, и строкой с context array:

```php
logger()->info('Import started');
logger()->warning("User $userId not found");
logger()->error('Webhook failed', ['payload_id' => $payloadId]);
```

Главное правило:
- не логировать и не `report(...)` исключение перед пробросом без новой полезной информации;
- если исключение и так будет проброшено и обработано глобально, не создавай дублирующий шум.

Плохо:

```php
try {
    // ...
} catch (\Throwable $e) {
    logger()->error($e->getMessage());

    throw $e;
}
```

Нормально, если добавляешь смысл:

```php
try {
    // ...
} catch (\Throwable $e) {
    report($e);
    logger()->warning('Invoice sync failed', ['invoice_id' => $invoiceId]);

    throw $e;
}
```
