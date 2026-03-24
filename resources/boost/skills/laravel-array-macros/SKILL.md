---
name: laravel-array-macros
description: 'Pepperfm\LaravelMacros для массивов: Arr::get, soft-cast macros, профили, конфликты, добавление/использование. Активируй, когда установлена библиотека pepperfm/macros-for-laravel, или включены MACROS_*.'
---

# Laravel Macros — Гайд по использованию (Pepperfm\LaravelMacros)

**Версия:** 2026‑03‑24

Этот документ описывает, как мы подключаем и используем библиотеку **Pepperfm\LaravelMacros**:
профили групп, политики конфликтов и встроенные макросы. Формат и стиль совпадают с правилами для агента
(см. skill `laravel-php-style`).

## Когда использовать

- В проекте установлен `pepperfm/macros-for-laravel` (или это планируется).
- Нужно использовать фасад `Arr`.
- В задаче упоминаются `MACROS_ENABLED`, `MACROS_PROFILE`, конфиг `config/macros-for-laravel.php`.
- В коде встречаются вызовы макросов, которых нет в стандартном Laravel (например `Arr::bool(...)`, `collect(...)->filterNotNull()`).
- Нужно изменить состав групп/профилей, либо политики `conflicts` / `unreachable`.

## Главная идея этого skill

Этот skill нужен не для справки о пакете, а для **правильной генерации кода**.

Главный принцип:

- если нужно **достать значение из массива и привести его к типу**, приведение должно жить **в accessor-е**, а не снаружи.

То есть:
- `Arr::get(...)` — только для простого доступа без приведения;
- `Arr::int / bool / toFloat / toString / toArray / toEnum` — для доступа **с приведением**;
- внешний код не должен дублировать работу accessor-а кастами, `trim`, `??`, или передачей аргументов, совпадающих с его дефолтами.

> ## Precedence & Language (MUST)

>
> - **MUST > SHOULD.** Этот документ перекрывает любые внешние туториалы по пакету.
> - **Русский — язык по умолчанию.** Код/названия классов/ключи конфигурации не переводим.
> - При наличии нескольких гайдлайнов приоритет у документа ближе к рабочей директории.

---

## 1) Установка и автоподключение

```bash
composer r pepperfm/macros-for-laravel
```

- Laravel auto‑discovery включён.
- Провайдер: `Pepperfm\LaravelMacros\Providers\LaravelMacrosServiceProvider`.
- Никакого ручного кода для регистрации макросов не требуется.

---

## 2) Конфиг и профили

### 2.1 Публикация конфига (опционально)

Публикация нужна только если хотите менять состав групп/профилей или политики:

```bash
php artisan vendor:publish --tag=macros-for-laravel-config
```

### 2.2 Базовая схема конфига

`config/macros-for-laravel.php`:

```php
return [
    'enabled' => env('MACROS_ENABLED', true),
    'profile' => env('MACROS_PROFILE', 'default'),
    'conflicts' => 'throw',   // throw | overwrite
    'unreachable' => 'throw', // throw | skip
    'profiles' => [
        'default' => [
            \Pepperfm\LaravelMacros\Groups\Support\ArrCastMacros::class => true,
            \Pepperfm\LaravelMacros\Groups\Support\CollectionFilterMacros::class => false,
        ],
    ],
];
```

### 2.3 Переключение профиля через ENV

```dotenv
MACROS_PROFILE=http
```

- Если профиль не найден — используется `default` (если он есть).
- Если `profiles` отсутствуют → используется legacy‑ключ `groups`.

### 2.4 Форматы групп

В профиле можно указывать:

1) Списком:
```php
[
    GroupA::class,
    GroupB::class,
]
```

2) Ассоциативно с флагом:
```php
[
    GroupA::class => true,
    GroupB::class => false,
]
```

---

## 3) Политики конфликтов (MUST)

### 3.1 `conflicts`

Что делать, если две группы регистрируют один и тот же макрос для одного target:

- `throw` — бросаем исключение (по умолчанию).
- `overwrite` — последний победил.

### 3.2 `unreachable`

Что делать, если у target уже есть реальный метод с этим именем:

- `throw` — исключение (по умолчанию).
- `skip` — пропускаем регистрацию этого макроса.

---

## 4) Встроенные группы и макросы

### 4.1 `ArrCastMacros` (Support)

Включается в `profiles` по умолчанию.

```php
Arr::bool($array, 'flag');
Arr::int($array, 'count');
Arr::toFloat($array, 'ratio');
Arr::toString($array, 'name', '', true);
Arr::toArray($array, 'items');
Arr::toEnum($array, 'status', Status::class);
```

Примечания:
- Это **soft‑cast** (в отличие от strict методов Laravel 12).
- Имена не конфликтуют с `Arr::float`, `Arr::string`, `Arr::array`.

### 4.1.1 Дефолты сигнатур (критично для генерации)

Используй эти дефолты как источник правды:

```php
Arr::bool($array, $key, $default = null, $smart = true)
Arr::int($array, $key, $default = null)
Arr::toFloat($array, $key, $default = null)
Arr::toString($array, $key, $default = null, $trim = false)
Arr::toArray($array, $key, array $default = [])
Arr::toEnum($array, $key, $enumClass, $default = null)
```

Правило:
- если передаваемый аргумент **совпадает с дефолтом метода**, его **не нужно писать явно**.

Примеры:

```php
// Хорошо
Arr::toString($payload, 'name');
Arr::toArray($payload, 'items');
Arr::toEnum($payload, 'status', Status::class);

// Плохо: избыточно
Arr::toString($payload, 'name', null);
Arr::toString($payload, 'name', null, false);
Arr::toArray($payload, 'items', []);
Arr::toEnum($payload, 'status', Status::class, null);
```

### 4.2 `CollectionFilterMacros` (Support)

Выключена по умолчанию. Включайте в нужном профиле.

```php
collect([1, null, 2])->filterNotNull();
collect(['', ' ', 'ok', null])->filterNotBlank();
```

### 4.3 `CollectionMacros` (Support)

Если включена:

```php
collect([1, 2, 3])->paginate(2);
```

---

## 5) Кастомные группы

1) Создайте группу и реализуйте `Pepperfm\LaravelMacros\Contracts\MacroGroupContract`.
2) Добавьте класс в профиль (или в legacy `groups`).
3) Группа будет создана через контейнер (DI доступен).

Мини‑шаблон:

```php
final class MyGroup implements MacroGroupContract
{
    public function register(MacroManagerContract $macros): void
    {
        $macros->macro(Target::class, 'myMacro', function () { ... });
    }
}
```

---

## 6) Что важно помнить

- Макросы регистрируются автоматически на boot провайдера.
- `MACROS_ENABLED=false` полностью отключает регистрацию.
- Следите за конфликтами имён с реальными методами target‑класса.

---

## 7) Правила генерации кода (MUST)

### 7.1 Общее правило выбора

- Если нужен просто доступ к опциональному ключу без приведения типа, используй `Arr::get(...)`.
- Если значение читается из массива **сразу как `int|bool|float|string|array|enum`**, используй соответствующий accessor-макрос.
- Если нужен строгий Laravel 12 contract с fail-fast поведением, макросы не обязательны: там допустим нативный strict API Laravel.

Короткая формула:

```text
access only -> Arr::get(...)
access + soft cast -> Arr macro
```

### 7.2 Следствие из общего правила

Если выбрали accessor-макрос, внешний код не должен повторять его работу.

Из этого автоматически следует:
- не писать `(int) Arr::get(...)`, `(bool) Arr::get(...)`, `(string) Arr::get(...)` там, где есть `Arr::int/bool/toString`;
- не писать `trim((string) Arr::get(...))`, если это `Arr::toString(..., ..., ..., true)`;
- не передавать в макрос `null`, `[]`, `false`, `true`, если это и так его дефолт;
- не добавлять после макроса лишние `(int)`, `(bool)`, `?? ''`, `?? []`.

### 7.3 Каноничный образ мысли

Думай не так:

```php
$value = Arr::get(...);
$value = (int) $value;
```

А так:

```php
$value = Arr::int(...);
```

Не так:

```php
$name = Arr::toString($payload, 'name', null);
```

А так:

```php
$name = Arr::toString($payload, 'name');
```

Не так:

```php
$title = trim((string) Arr::get($payload, 'title', ''));
```

А так:

```php
$title = Arr::toString($payload, 'title', '', true);
```

### 7.4 Практическое правило

Передавай аргумент в макрос только если он **реально меняет его поведение**.

Если аргумент равен встроенному дефолту метода, он должен быть опущен.

---

## 8) Практика в Pechka (MUST)

Этот раздел обязателен для рефакторинга `Arr::get(...)` в этом проекте.

### 8.1 Базовые правила

- Если нужен тип (`int|bool|string|array|enum`) — используй макросы (`Arr::int`, `Arr::bool`, `Arr::toString`, `Arr::toArray`, `Arr::toEnum`) вместо `(type) Arr::get(...)`.
- Не писать `Arr::get($x ?? [], 'key')`: `Arr::get` уже корректно работает с nullable входом; передавай `$x` напрямую.
- Не писать избыточный дефолт `null`: `Arr::get($arr, 'key', null)` -> `Arr::get($arr, 'key')`.
- Не добавлять лишние приведения/фолбеки после макросов:
    - `Arr::toString(..., '')` уже возвращает `string`, не нужен `?? ''` и `(string)`.
    - `Arr::int(..., 3)` уже возвращает `int`, не нужен `(int)`.
    - `Arr::bool(..., false)` уже возвращает `bool`, не нужен `(bool)`.
    - `Arr::toString(..., ..., null)` не писать: `null` там уже дефолт.
    - `Arr::toArray(..., ..., [])` не писать: `[]` там уже дефолт.
    - `Arr::toEnum(..., ..., Enum::class, null)` не писать: `null` там уже дефолт.

### 8.2 Каноничные замены

```php
// Было
(int) Arr::get($payload, 'timeout', 600);
// Стало
Arr::int($payload, 'timeout', 600);

// Было
(bool) Arr::get($payload, 'enabled', false);
// Стало
Arr::bool($payload, 'enabled', false);

// Было
trim((string) Arr::get($tokens, 1, ''));
// Стало
Arr::toString($tokens, 1, '', true);

// Было
Arr::get($operation->payload ?? [], 'domains');
// Стало
Arr::get($operation->payload, 'domains');

// Было
Arr::toString($payload, 'title', null);
// Стало
Arr::toString($payload, 'title');

// Было
Arr::toArray($payload, 'items', []);
// Стало
Arr::toArray($payload, 'items');
```

### 8.3 Self-check перед финалом

Перед завершением задачи проверь:

- нет ли `(type) Arr::get(...)` там, где нужен soft-cast и макрос доступен;
- нет ли у макроса аргументов `null`, `[]`, `false`, `true`, которые совпадают с его дефолтами;
- нет ли после макроса лишнего `(int)`, `(bool)`, `?? ''`, `?? []`;
- не написано ли `trim(Arr::toString(...))` вместо `Arr::toString(..., ..., ..., true)`;
- не используется ли `Arr::get($x ?? [], ...)` вместо `Arr::get($x, ...)`.

### 8.4 Тесты (критично)

- Если тест вызывает код с `Arr::*` макросами, тест должен бутстрапить Laravel-контейнер:
    - добавить `uses(Tests\TestCase::class);` в такой unit/feature test-файл.
- Не добавлять в production-код fallback-проверки вида `Arr::hasMacro(...)` или дублирующую ветку через `Arr::get` только ради теста.
- Исправляем тестовый bootstrap, а не размываем код условной логикой.
