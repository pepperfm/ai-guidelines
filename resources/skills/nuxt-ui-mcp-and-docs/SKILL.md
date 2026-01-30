---
name: nuxt-ui-mcp-and-docs
description: 'MCP Nuxt UI и локальное зеркало доков: как искать компоненты/props/slots и проверять поведение. Активируй при любых вопросах по API Nuxt UI.'
---

# Skill: Nuxt UI — MCP & Docs Mirror

**Версия:** 2026‑01‑30

**Когда использовать:**
- Любые вопросы про Nuxt UI: компоненты, пропсы, слоты, темы, composables, router‑режимы.
- Нужно быстро и точно получить актуальный API/пример, не полагаясь на «память» модели.

---

## 1) Nuxt UI MCP server (MUST)

- В окружении настроен MCP‑сервер **`nuxt-ui`** (обычно в `~/.codex/config.toml`).
- При **любой** работе с Nuxt UI:
  1) **сначала** используем MCP `nuxt-ui` как источник правды;
  2) только затем дополняем ответ контекстом проекта и нашими конвенциями.

### Рекомендуемые инструменты MCP

- Подбор компонента:
  - `find_component_for_usecase`, `list_components`
- Актуальный API:
  - `get_component_metadata` (если нужны только props/slots/events)
  - `get_component` / `get_documentation_page` (просить конкретные `sections`: `usage`, `api`, `examples`)
- Примеры из доков:
  - `list_examples`, `get_example`
- Темплейты/миграции:
  - `list_templates`, `get_template`, `get_migration_guide`

### Как экономить контекст (важно)

- **Не тащи целую страницу доки**, если нужна 1 деталь.
- Сначала пробуй `get_component_metadata`.
- Если нужна страница — проси только нужные `sections`.

### Фоллбеки

- Если MCP недоступен/500/таймаут — допускается обращаться к домену `ui.nuxt.com` (или локальному зеркалу ниже).

---

## 2) Локальное зеркало доков Nuxt UI (MUST, если присутствует)

Если в проекте есть папка **`.ai/nuxtui/`**:

- Используй зеркало доков в `.ai/nuxtui/raw/docs/**`.
- Используй `.ai/nuxtui/llms.txt` как индекс (чтобы быстро найти нужный markdown‑файл).
- Для props/slots/examples открывай **1–2** релевантных `.md` — не «сканируй всё».

---

## 3) Нормальный рабочий цикл

1) Сформулируй задачу (какой компонент/паттерн нужен).
2) Дёрни MCP (`find_component_for_usecase` → `get_component_metadata`).
3) Если нужно — добери `examples`.
4) Применяй в коде с учётом конвенций проекта (см. skill `nuxt-ui-patterns`).
