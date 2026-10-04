# Nuxt UI — Minimal Integration Guidelines

**Версия:** 2026-10-04

Этот preset намеренно не содержит локального справочника компонентов и не устанавливает Nuxt UI skills. API Nuxt UI меняется достаточно быстро, поэтому props/slots/examples нужно брать из актуального MCP или официальной документации установленной версии.

## Source of truth

- Сначала определи установленную версию `@nuxt/ui` из package manager lockfile.
- Для component API, props, slots и examples используй Nuxt UI MCP / официальные version-matching docs, а не память модели.
- Не поддерживай локальные копии больших фрагментов Nuxt UI docs в этом пакете.

## Laravel + Inertia invariants

- Если проект использует Nuxt UI в Vue/Vite + Inertia режиме, сохраняй существующую integration-схему проекта; не добавляй `nuxt.config.ts` или Nuxt runtime без явной причины.
- Не добавляй `vue-router`, если навигацией управляет Inertia.
- Корневой UI provider (`UApp`) и текущую настройку Vite/Nuxt UI считай частью project contract и проверяй по реальному коду перед изменением.
- Предпочитай компоненты Nuxt UI самописным заменам там, где библиотека уже предоставляет подходящий компонент.
