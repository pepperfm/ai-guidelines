# Laravel Macros — Quick Pointer

**Версия:** 2026-10-04

Подробные правила по `Pepperfm\LaravelMacros` живут в skill `laravel-array-macros` и не должны постоянно занимать контекст.

Подключай skill, только если:

- проект действительно использует `pepperfm/macros-for-laravel`;
- задача касается `Arr::*` soft-cast accessors или конфигурации macros;
- нужно менять/проверять профиль macros.

Короткий принцип:

- `Arr::get(...)` — доступ без приведения;
- `Arr::int / bool / toFloat / toString / toArray / toEnum` — доступ с soft-cast;
- внешний код не должен повторять работу macro дополнительными cast/`trim`/`??`, если это уже часть контракта macro.
