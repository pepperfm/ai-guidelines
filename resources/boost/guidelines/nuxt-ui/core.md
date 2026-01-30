# Nuxt UI — Project Guidelines (Lite)

**Версия:** 2026‑01‑30

Этот файл — **тонкий**: только MUST/ограничения по Nuxt UI в стеке *Laravel + Inertia + Vite + Tailwind*.
Детальная интеграция, паттерны и примеры вынесены в `.ai/skills/nuxt-ui-*`, чтобы экономить контекст/токены.

> Общие правила см. `.ai/guidelines/01-core.md`. Laravel‑правила см. `10-laravel.md`.

---

## 1) Skills (подключай по необходимости)

- `nuxt-ui-mcp-and-docs` — работа с MCP-сервером Nuxt UI + локальное зеркало доков.
- `nuxt-ui-integration` — установка, `vite.config.ts`, `app.ts`, CSS, `UApp`, `isolate`.
- `nuxt-ui-patterns` — архитектура UI, overlays, формы, темизация, примеры, TL;DR.

---

## 2) MUST

- **Источник правды по Nuxt UI — MCP**: при вопросах про компоненты/props/slots сначала используем MCP `nuxt-ui`.
- Экономим контекст: через MCP просим только нужное (`get_component_metadata`, `sections=...`).
- Если MCP недоступен и в проекте есть `.ai/nuxtui/` — это локальное зеркало доков, используем его.
- В `vite.config.ts` Nuxt UI в режиме Inertia: `ui({ router: 'inertia' })`.
- Корневой layout оборачиваем в `<UApp>`.
- В Blade/Inertia‑корне ставим класс `isolate` (чтобы не ломались overlay‑слои/z-index).
- Не импортируем `useToast()` / `useOverlay()` вручную: они auto-import.
- Programmatic overlays (`overlay.create(...)`) держим рядом с триггером (страница/компонент), composables — только для бизнес-логики.

---

## 3) MUST NOT

- Не подключать `vue-router`, если проект работает с `router: 'inertia'` и роутинг обеспечивает Inertia.
- Не строить UI из «голых div+border», если есть эквивалентный компонент Nuxt UI.
- Не тащить огромные куски доков в ответ: даём краткое резюме + ссылку/указание на skill.
