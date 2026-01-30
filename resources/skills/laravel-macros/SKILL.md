---
name: laravel-macros
description: 'Pepperfm\LaravelMacros: профили, конфликты, добавление/использование макросов. Активируй, когда установлена библиотека pepperfm/macros-for-laravel, или включены MACROS_*.'
---

# Laravel Macros — Гайд по использованию (Pepperfm\LaravelMacros)

**Версия:** 2026‑01‑19

Этот документ описывает, как мы подключаем и используем библиотеку **Pepperfm\LaravelMacros**:
профили групп, политики конфликтов и встроенные макросы. Формат и стиль совпадают с
`10-laravel.md` / `11-nuxt-ui.md`.

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
Arr::toString($array, 'name', null, true);
Arr::toArray($array, 'items');
Arr::toEnum($array, 'status', Status::class, $default = null);
```

Примечания:
- Это **soft‑cast** (в отличие от strict методов Laravel 12).
- Имена не конфликтуют с `Arr::float`, `Arr::string`, `Arr::array`.

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
