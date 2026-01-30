---
name: nuxt-ui-integration
description: 'Интеграция Nuxt UI в Laravel 12 + Inertia + Vite: vite config, app.ts, CSS, UApp, isolate.'
---

# Skill: Nuxt UI — Интеграция с Laravel 12 + Inertia + Vite

**Версия:** 2026‑01‑30

**Когда использовать:**
- Проект Laravel + Inertia + Vite подключает Nuxt UI (Vue/Vite режим).
- Нужно правильно настроить `vite.config.ts`, точку входа Vue, CSS, корневой `UApp` и базовый Blade...

> API компонентов всё равно сначала берём через MCP (см. skill `nuxt-ui-mcp-and-docs`).

---

## 1) Установка пакета

```bash
bun add @nuxt/ui tailwindcss
```

> Мы подразумеваем bun, но если в проекте принят npm/pnpm/yarn — следуем проектным договорённостям.

---

## 2) Vite config (Laravel + Inertia)

`vite.config.ts`:

```ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import ui from '@nuxt/ui/vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.ts'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    ui({
      router: 'inertia',
      // Здесь же можно задать prefix/ui/theme — см. skill `nuxt-ui-patterns`
    }),
  ],
})
```

Примечания:
- **`router: 'inertia'`**: Nuxt UI использует Inertia‑адаптер вместо `RouterLink`.
- В новых версиях Nuxt UI опция — именно `router` (старое `inertia: true` встречается в устаревших примерах).
- При `router: 'inertia'` (или `router: false`) зависимость `vue-router` **не требуется**.
- Nuxt UI подключает `unplugin-auto-import` и `unplugin-vue-components`: хуки (`useToast`, `useOverlay`) и компоненты импортируются автоматически.

---

## 3) Подключение Vue‑плагина Nuxt UI

`resources/js/app.ts`:

```ts
import '../css/app.css'
import type { DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import ui from '@nuxt/ui/vue-plugin'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel x Nuxt UI'

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ui)
      .mount(el)
  },
})
```

> `ui` — Vue‑плагин Nuxt UI, который регистрирует компоненты, темы и composables.

---

## 4) Tailwind v4 + Nuxt UI CSS

`resources/css/app.css`:

```css
@import 'tailwindcss';
@import '@nuxt/ui';
```

---

## 5) Обёртка `UApp` и корневой контейнер (MUST)

### Vue‑часть

Оберни Inertia‑контент в `UApp` (обычно в базовом layout):

```vue
<template>
  <UApp>
    <slot />
  </UApp>
</template>
```

`UApp` нужен для:
- глобальной конфигурации темы;
- корректной работы Toast / Tooltip / programmatic overlays (`useOverlay`).

### Blade‑шаблон

`resources/views/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @inertiaHead
    @vite('resources/js/app.ts')
  </head>
  <body>
    @inertia({ class: 'isolate' })
  </body>
</html>
```

Класс **`isolate`** на корневом контейнере:
- создаёт отдельный stacking‑context для overlay‑слоёв Nuxt UI;
- снижает риск z‑index конфликтов.
