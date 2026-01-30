# Element Plus + Vue 3 — Project Guidelines (Lite)

**Версия:** 2026‑01‑30

Этот файл — **тонкий**: только MUST/ограничения. Полный гайд с примерами вынесен в skill `element-plus-guide`.

---

## 1) Skills

- `element-plus-guide` — полный гайд: подключение, layout, формы, таблицы, диалоги, уведомления, иконки, типовые CRUD‑паттерны.

---

## 2) MUST

- Фронтенд строим на **Vue 3** и **Element Plus**.
- Не подключаем другие UI‑фреймворки, если это явно не попросили.
- Для бизнес-UI (формы, таблицы, модалки, уведомления) используем компоненты Element Plus, если это возможно.
- Компоненты пишем на Vue 3 с `<script setup>` и Composition API.
- Для сообщений/confirm используем `ElMessage` / `ElNotification` / `ElMessageBox`, не `alert()`.

## 3) MUST NOT

- Не генерировать «сырой» HTML `<table>` для бизнес‑данных, если `el-table` подходит.
- Не переопределять тему/стили огромными инлайн‑стилями без необходимости.

> Детали и примеры: `.ai/skills/element-plus-guide/SKILL.md`.
