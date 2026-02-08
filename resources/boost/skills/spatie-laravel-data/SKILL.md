---
name: spatie-laravel-data
description: 'Практики и конвенции для использования spatie/laravel-data (v4) в Laravel: DTO/Resource, from/collect/fromModel, snake_case маппинг, Lazy props, валидация (rules + ValidationContext), интеграция с контроллерами и Eloquent casts.'
---

# Skill: Spatie Laravel Data (v4)

**Версия:** 2026‑02‑02  
**Пакет:** `spatie/laravel-data` v4.x

Эта skill задаёт **единый “Laravel‑way”** стиль использования пакета **spatie/laravel-data**:
- Data‑классы как DTO **и** как Resource (ответы контроллеров).
- Минимум ручной маппинг‑рутины, максимум типизации/инференса.
- Читаемость, предсказуемость, KISS/DRY.

**Когда использовать:**
- Нужно описать вход/выход HTTP в виде типизированного Data‑класса (DTO/Resource), вместо `array` и ручного маппинга.
- Нужно единообразие `snake_case` во внешнем API при `camelCase` в PHP.
- Нужны `rules()`/валидация и нормализация данных рядом с типами (вместо разрозненных `FormRequest` + ручного маппинга).
- Нужно преобразование Model/Collection/Paginator в JSON ответ без “простынь” в контроллерах.

---

## Проектные конвенции (MUST)

### 1) Имена свойств — только `camelCase`

- **В PHP‑классе** все свойства Data‑класса (и параметры конструктора) — **`camelCase`**.
- **Во внешнем API** (HTTP JSON, массивы) — **`snake_case`** достигается маппингом имён (см. ниже).
- Не пиши `snake_case` свойства в Data‑классе даже если так в payload — используем маппинг.

### 2) На классе всегда стоит маппинг в `snake_case`, если пользователь не просит об ином

Над каждым Data‑классом ставь:

```php
#[MapName(SnakeCaseMapper::class)]
```

Это обеспечивает **одинаковое правило** для:
- входящего payload (создание через `from()`/инъекции из Request),
- исходящего payload (ответы/`toArray()`/`toJson()`).

> Можно настроить глобально в `config/data.php`, но стоит предпочитать **явный атрибут на каждом классе**.

### 3) `use`‑импорты: базовый класс — ПЕРВЫМ

Если класс расширяет `Data`, то **первый импорт** — `use Spatie\LaravelData\Data;`  
Если расширяет `Dto`, то первым импортом будет `use Spatie\LaravelData\Dto;`  
И т.д.

---

## Базовый шаблон Data‑класса

```php
<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data; // базовый класс — всегда первым
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ExampleData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $subtitle,
    ) {
    }
}
```

Рекомендации:
- `final` по умолчанию (если нет причины расширять).
- `readonly` для иммутабельных DTO (обычно да).
- Конструктор‑promotion, типы, `?type` для nullable.


## Выбор базового класса: `Data` vs `Resource` vs `Dto`

По умолчанию используй **`Data`**.

Используй другие базовые классы только когда это реально упрощает:

- **`Resource`** — когда класс нужен *только для вывода* (как API Resource).  
  Он **не валидирует** и **не делает authorize** при создании — обычно чуть быстрее, и намерение в коде яснее.
- **`Dto`** — когда класс нужен *только как DTO внутри приложения* (без resource‑фич: wrapping/includes/…).

Конвенция по импортам остаётся: **базовый класс, который расширяем, импортируем первым**:

```php
use Spatie\LaravelData\Resource; // если extends Resource
// ...
```

---

## Создание Data объектов: `new`, `from`, `collect`, `optional`

### `new` (когда данные уже типизированы)
Используй, когда ты **уже в домене** и у тебя корректные типы.

### `::from($payload)`
Стандартный путь. `from()` умеет создавать Data‑объекты из:
- массива,
- Request,
- Eloquent модели (и многих “arrayable” объектов).

**Правило:** если есть Data‑класс — **не таскай массивы дальше по коду**, поднимай типизацию как можно ближе к границе (controller/request).

### `::collect($items)`
Для коллекций/массивов элементов:

```php
return SongData::collect(Song::query()->latest()->get());
```

Также `collect()` умеет работать с paginator и возвращать пагинированный JSON‑ответ.

### `::optional($value)`
Если вход может быть `null` и это нормально:

```php
$userData = UserData::optional($userOrNull); // вернёт null либо UserData
```


### Optional properties для PATCH/частичных апдейтов

Если поле **может отсутствовать в payload** (не `null`, а именно *ключа нет*) — используй `Optional`:

```php
use Spatie\LaravelData\Optional;

public function __construct(
    public string $name,
    public string|Optional $bio, // bio может быть не передан вовсе
) {
}
```

Правило:
- `?string` = ключ есть, но значение может быть `null`.
- `string|Optional` = ключ может не прийти вообще (удобно для PATCH).


---

## Инъекция Data в контроллеры (Laravel‑way)

**Предпочитай**: тип‑хинт **`Data`**‑класса в контроллере вместо ручного `Request` + `validate()`.

> Если это чистый `Resource` (extends `Resource`) — не используй его для инъекции/валидации, он предназначен только для трансформации вывода.

```php
final class PostController
{
    public function store(PostData $data)
    {
        // 1) Payload уже валиден
        // 2) $data уже создан из Request
        Post::create($data->toArray());

        return back();
    }
}
```

Полезно для отладки:
- `PostData::getValidationRules($payload)` — посмотреть, какие правила генерируются.

---

## Валидация: инференс + атрибуты + ручные правила

### Когда запускается валидация
- Автоматически при **инъекции Data из Request**.
- Автоматически при `Data::from($request)`.
- В остальных случаях — **вручную** через `validate()` / `validateAndCreate()`.


Также можно добавить авторизацию прямо в Data‑класс:

```php
public static function authorize(): bool
{
    return auth()->check();
}
```

Если `authorize()` вернёт `false`, будет выброшен `AuthorizationException` (аналогично FormRequest).


### 1) Используй типы + атрибуты, где возможно
- Типы дают базовые правила (`required/nullable`, `string`, `integer`, `boolean`, `array`, `enum`, …).
- Атрибутами добавляй то, что типами не выразить (например `#[Date]`, `#[Max(20)]`, `#[Exists(...)]`, `#[Unique(...)]` и т.д.).

### 2) Ручные правила: `public static function rules(...) : array`
Если нужно собрать сложный `Rule` объект или правила зависят от контекста — пиши ручные правила.

**Важно:**
- Используй **array‑syntax** (`['required', 'string']`), а не строку `'required|string'`.
- Если в `rules()` переопределяешь правила для поля — **авто‑инференс для этого поля не применится**.
- Если нужно **объединить** ручные и авто‑правила — используй `#[MergeValidationRules]` (импорт: `use Spatie\\LaravelData\\Attributes\\MergeValidationRules;`).

### 3) Контекст: `ValidationContext $context` (и имя переменной MUST быть `$context`)

```php
use Spatie\LaravelData\Support\Validation\ValidationContext;

public static function rules(ValidationContext $context): array
{
    return [
        'artist' => Rule::requiredIf(($context->fullPayload['title'] ?? null) !== 'Never Gonna Give You Up'),
    ];
}
```

- `$context->fullPayload` — исходный полный payload.
- `$context->payload` — “относительный” payload для nested‑Data.


> Note: при маппинге имён (например snake_case) правила генерируются для **mapped** имени поля, но текст ошибки может ссылаться на исходное имя свойства — учитывай это при UX/локализациях.

### 4) Хуки валидатора, сообщения, атрибуты
Если нужно:
- `public static function messages(): array`
- `public static function attributes(): array`
- `public static function withValidator(Validator $validator): void`
- `redirect()`, `redirectRoute()`, `stopOnFirstFailure()`, `errorBag()` — тоже можно переопределять.

---

## Кастомизация создания из модели: `fromModel(...)`

Если дефолтное создание из модели не подходит — добавь:

```php
public static function fromModel(Post $post): self
{
    return new self(
        title: $post->title,
        // ...
    );
}
```

`from()` автоматически вызовет `fromModel()` при передаче `Post` в `from()`.

---

## Lazy properties (включаемые поля, отношения, тяжёлые вычисления)

### Как объявлять
Тип свойства делай `Lazy|T`:

```php
use Spatie\LaravelData\Lazy;

public function __construct(
    public readonly string $title,
    public readonly Lazy|array $comments, // например массив CommentData
) {
}
```

### Как создавать Lazy значения
- `Lazy::create(fn () => ...)`
- `Lazy::when(fn () => condition, fn () => ...)`
- `Lazy::whenLoaded('relation', $model, fn () => ...)`

### Как включать Lazy поля в ответ
По умолчанию lazy‑поля **не попадают** в `toArray()/response`, пока ты не вызовешь:

```php
PostData::from($post)->include('comments');
PostData::from($post)->include('comments.{author, body}');
PostData::from($post)->include('comments.*');
```

Есть также `exclude`, `only`, `except`, `includeWhen`, `excludeWhen`, `onlyWhen`, `exceptWhen`.

### Auto‑lazy для отношений
Для отношений Eloquent удобно:
- `#[AutoLazy]`
- `#[AutoWhenLoadedLazy]` (и можно указать имя relation, если отличается от имени свойства)

> Важно: свойства в проекте всё равно `camelCase`, а наружу они выйдут `snake_case` из‑за `MapName(SnakeCaseMapper::class)`.

---

## Нестинг и коллекции Data‑объектов

Если внутри Data есть коллекция Data‑объектов — **обязательно укажи тип элементов**, чтобы нормально генерировались правила и корректно работали partial/includes.

Допускается — аннотации/дженерики (лучше для IDE и PHPStan):

```php
use Illuminate\Support\Collection;

/**
 * @param Collection<int, SongData> $songs
 */
public function __construct(
    public readonly string $title,
    public readonly Collection $songs,
) {
}
```

Предпочтительно — атрибут:

```php
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Illuminate\Support\Collection;

public function __construct(
    public readonly string $title,
    #[DataCollectionOf(SongData::class)]
    public readonly Collection $songs,
) {
}
```

---

## Data как Resource: ответы контроллеров

Возвращай Data прямо из контроллера — Laravel Data сам преобразует в JSON:

```php
public function show(Post $post): PostData
{
    return PostData::from($post);
}
```

Коллекции и пагинация:

```php
return PostData::collect(Post::query()->paginate());
```

---

## Eloquent casts (когда Data хранится в JSON‑полях)

Для JSON/array‑полей (настройки, конфиги, профили и т.п.) — используй касты:

```php
protected $casts = [
    'profile' => ProfileData::class,
];
```

Также можно кастить коллекции через `DataCollection::class . ':' . ItemData::class` (см. доки).

---

## Антипаттерны (DON'T)

- ❌ Data‑класс со свойствами `snake_case` (в PHP).
- ❌ Внутри Data делать сложную бизнес‑логику/доступ к БД (максимум — лёгкое форматирование/маппинг).
- ❌ Таскать массивы через сервисы вместо DTO.
- ❌ Валидировать “после создания” Data — по смыслу Data предполагается валидным после создания.
- ❌ Делать “универсальные” мегаклассы на 50+ полей без нужды — лучше несколько Data на разные use‑cases.

---

## Короткий чек‑лист перед коммитом (для агента)

- [ ] Свойства Data‑класса в `camelCase`
- [ ] На классе есть `#[MapName(SnakeCaseMapper::class)]`
- [ ] Импорт базового класса (`Data`/`Dto`) — самый первый `use`
- [ ] Есть типы, nullable где нужно, коллекции типизированы
- [ ] Валидация: инференс/атрибуты/`rules()` по необходимости
- [ ] Lazy поля сделаны лениво и включаются через `include()` (если надо)
- [ ] Контроллеры принимают/возвращают Data (где уместно), без лишнего boilerplate
