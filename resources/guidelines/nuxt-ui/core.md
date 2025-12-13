# Nuxt UI — Project Guidelines (Laravel 12 + Vite + Inertia + Tailwind v4)

**Версия:** 2025‑11‑13

Этот документ описывает, как мы используем **Nuxt UI (Vue/Vite‑режим)** в стеке *Laravel 12 + Inertia + Tailwind v4* — чтобы Codex/разработчики писали совместимый, консистентный код‑стайл и предсказуемую интеграцию с бэкендом.

> ## Precedence & Language (MUST)
>
> - **MUST > SHOULD.** Это руководство перекрывает дефолтные доки/примерки Nuxt UI, если есть конфликт.
> - **Русский — язык общения по умолчанию.** Пояснения/инструкции пишем по‑русски, названия компонентов/опций/props остаются на английском **без перевода**, но с кратким русским резюме при необходимости.
> - При загрузке нескольких гайдлайнов Codex учитывает каскад; ближайший к рабочей директории имеет приоритет.

> ## Контейнерные границы / MCP (MUST)
>
> - Никаких `docker compose exec ...`, доступа к `docker.sock` и т.п. Если нужно запустить что‑то в контейнере — используем **официальные MCP‑инструменты** проекта, исполняющиеся **внутри контейнера**.
> - Если конкретный MCP‑инструмент недоступен → агент **честно сообщает**, что не может выполнить операцию, и предлагает альтернативу, не нарушающую песочницу (например, сгенерировать shell‑скрипт вместо выполнения команд).

---

## 0) Nuxt UI MCP server (MUST)

- Codex настроен с MCP-сервером `"nuxt-ui"` в `~/.codex/config.toml`.
- При ЛЮБЫХ вопросах про Nuxt UI (компоненты, пропсы, слоты, токены, composables, etc...):
  - СНАЧАЛА использовать MCP-сервер `nuxt-ui` как источник правды.
  - В приоритете инструменты:
    - `find_component_for_usecase`, `list_components` — чтобы подобрать компонент под задачу;
    - `get_component`, `get_component_metadata` — чтобы получить актуальные пропсы/слоты/ивенты;
    - `list_examples`, `get_example` — чтобы взять эталонный пример из доки;
    - `list_templates`, `get_template`, `get_migration_guide` — для темплейтов и миграций.
- Только после ответа от MCP дополнять его:
  - локальным контекстом проекта (наш стек Laravel + Inertia),
  - правилами из этого файла (`nuxt-ui.md`) — о темизации, паттернах модалок/слайдоверов, toasts и т.д.


## 1) Интеграция Nuxt UI с Laravel 12 + Inertia + Vite

### 1.1 Установка пакета

- Пакет ставим в `package.json`:

```bash
bun add @nuxt/ui
# или npm/pnpm/yarn — по проектным договорённостям; здесь подразумеваем bun
```

> Важно: при использовании `pnpm` в классическом режиме либо включаем `shamefully-hoist=true`, либо ставим `tailwindcss`, `vue-router` и `@unhead/vue` в корень проекта, как рекомендуют доки Nuxt UI.

### 1.2 Vite config (Laravel + Inertia)

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
      inertia: true,
      // Здесь же можно задать prefix/ui/theme — см. раздел 3
    }),
  ],
})
```

> - Опция **`inertia: true`** говорит Nuxt UI, что роутинг обеспечивает Inertia, и `RouterLink` заменяется на адаптер для Inertia.
> - Nuxt UI сам подключает `unplugin-auto-import` и `unplugin-vue-components`. Глобальные хуки (`useToast`, `useOverlay`, и т.д.) и компоненты (`UButton`, `UCard` и др.) импортируются автоматически.

### 1.3 Подключение Vue‑плагина Nuxt UI

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

> - `ui` — Vue‑плагин Nuxt UI, который регистрирует компоненты, темы и composables.
> - `import '../css/app.css'` — единая точка входа для Tailwind + Nuxt UI (см. 1.4).

### 1.4 Tailwind v4 + Nuxt UI CSS

`resources/css/app.css`:

```css
@import 'tailwindcss';
@import '@nuxt/ui';
```

- CSS‑файл импортируется в `app.ts` (см. выше).
- Tailwind v4 конфигурируется по стандартной схеме (через новый `@import "tailwindcss";` и tailwind.config в корне проекта).

---

### 1.5 Обёртка `UApp` и корневой контейнер

#### Vue‑часть

Вместо обычной корневой страницы/лейаута, оборачиваем контент в `UApp`. В Laravel‑стартере это обычно `resources/js/pages/index.vue` (или базовый layout):

```vue
<template>
  <UApp>
    <!-- Здесь рендерится текущая Inertia‑страница -->
    <slot />
  </UApp>
</template>
```

> `UApp` необходим для:
> - глобальной конфигурации темы;
> - корректной работы Toast, Tooltip и **programmatic overlays** (модалки/слайдоверы через `useOverlay`).

#### Blade‑шаблон

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
    <div class="isolate">
      @inertia
    </div>
  </body>
</html>
```

> Класс **`isolate`** на корневом контейнере:
> - гарантирует, что overlay‑слои/порталы от Nuxt UI живут в отдельном stacking‑context;
> - предотвращает конфликты z‑index с внешними стилями/Blade‑разметкой.

---

## 2) Архитектура UI‑слоя

### 2.1 Каркас страниц

- Страницы собираем из блоков **`UPage` → `UPageHeader` → `USection` / `UPageBody` / `UPageCard`** (или их локальных обёрток). Это гарантирует единые отступы/сетки/акции.
- **Не рисуем «карточки из div+border».** Вместо «ручной» верстки используем компоненты библиотеки:
  - **Карточки** — `UCard` / `UPageCard`
  - **Кнопки** — `UButton`
  - **Инпуты/формы** — `UForm`, `UInput`, `USelect`, `UTextarea`, `UCheckbox`, `URadio`, `UDateInput`, `USwitch`, `UToggle`
  - **Модалки/слайдоверы** — `UModal`, `USlideover` (через programmatic overlay)
  - **Табы/таблицы/тосты** — `UTabs`, `UTable`, Toast (через `useToast()`).
- Цвета/радиусы/тени — **через токены** Nuxt UI и Tailwind, а не произвольные классы/hex.

### 2.2 Programmatic modals/slideovers — компоненты-триггеры + composables

- **Не импортируем `useToast()` / `useOverlay()` вручную** (ни в страницах, ни в компонентах, ни в composables) — хуки auto-import'ятся плагином `@nuxt/ui/vue-plugin`, достаточно вызывать `useToast()` / `useOverlay()` в `<script setup>`.
- **`overlay.create(...)` всегда вызываем в компонентах**, рядом с кнопкой/ссылкой-триггером (страница или дочерний UI-компонент). В composables держим только бизнес-логику (формы, запросы, форматирование данных).

#### 2.2.1 Пример: кнопка, открывающая модалку «Contact»

Компонент-кнопка создаёт overlay и открывает модалку. Сама модалка лежит отдельным файлом.

`resources/js/components/modals/ContactButton.vue`

```vue
<script setup lang="ts">
import ContactModal from '@/components/modals/ContactModal.vue'

const overlay = useOverlay()
const contactModal = overlay.create(ContactModal)

function openModal() {
  contactModal.open({
    title: 'Contact us',
  })
}
</script>

<template>
  <UButton icon="i-lucide-mail" @click="openModal">
    Связаться
  </UButton>
</template>
```

`resources/js/components/modals/ContactModal.vue`

```vue
<script setup lang="ts">
const props = defineProps<{
  title?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function close() {
  emit('close')
}
</script>

<template>
  <UModal :title="props.title">
    <p class="text-sm text-muted-foreground">
      Здесь форма/контакты…
    </p>

    <template #footer>
      <UButton color="neutral" variant="outline" @click="close">
        {{ $t('common.close') }}
      </UButton>
    </template>
  </UModal>
</template>
```

> **Важно:** если нужна ленивая загрузка модалки, используем `defineAsyncComponent` в компоненте-триггере:
>
> ```ts
> import { defineAsyncComponent } from 'vue'
> 
> const ContactModal = defineAsyncComponent(
>   () => import('@/components/modals/ContactModal.vue'),
> )
> 
> const overlay = useOverlay()
> const contactModal = overlay.create(ContactModal)
> ```
>
> Не пишем `overlay.create(() => import('...'))` напрямую — `create` ожидает Vue-компонент, а не loader-функцию.

- Аналогично выносим тосты в `useNotify()` (единый стиль сообщений/продолжительность).

### 2.3 Состояния загрузки/пустоты

- Для асинхронных блоков показываем **`USkeleton` / `Placeholder`** и `UEmpty` (стандартизованные тексты из i18n). Никаких «прыжков» верстки.

### 2.4 Формы и валидация

- Схемы — на **Valibot** (или другой выбранный валидатор).
- Сабмит через `@inertiajs/vue3` (`router.post/put/delete` или `useForm`), ошибки сервера маппим в поля.
- Кнопки сабмита — с `:loading`/`disabled` на pending.

---

## 3) Темизация

- Базовые цвета/радиусы можно задать прямо в опциях Vite‑плагина `ui({ ui: { colors: {...} } })`.
- Если Tailwind подключён с префиксом (`@import "tailwindcss" prefix(tw);`), в Vite‑плагине Nuxt UI используем тот же префикс: `ui({ theme: { prefix: 'tw' } })`, иначе утилиты в темах компонентов будут собираться без префикса.
- Tailwind v4 — используем только поддерживаемые утилиты; deprecated‑классы не допускаются.

---

## 4) Конвенции кода (Vue)

- Только **`<script setup>`** в компонентах.
- Имена: `AppXxx`, `FeatureXxx`, секции — `SectionXxx`.
- **Props/Emits** типизируем через `defineProps/defineEmits` с TS‑интерфейсами.

### 4.1 ESLint
Проектный конфиг (обязателен):
```ts
import antfu from '@antfu/eslint-config'

export default antfu({
  vue: true,
  rules: {
    'unicorn/error-message': 'off',

    // Всегда фигурные
    'curly': ['error', 'all'],

    // 1TBS + запрет однострочных блоков { ... }
    '@stylistic/brace-style': ['error', '1tbs', { allowSingleLine: false }],

    '@stylistic/object-curly-spacing': ['error', 'always'],

    '@stylistic/object-curly-newline': ['error', {
      ObjectExpression: { minProperties: 5, multiline: true, consistent: true },
      ObjectPattern: { minProperties: 5, multiline: true, consistent: true },
      ImportDeclaration: { minProperties: 5, multiline: true, consistent: true },
      ExportDeclaration: { minProperties: 5, multiline: true, consistent: true },
    }],

    // Если атрибутов (props) ≤ 2 — можно в одну строку.
    // Если > 2 — переносим на новые строки, по одному на строку.
    'vue/max-attributes-per-line': ['error', {
      singleline: 2,
      multiline: { max: 1 },
    }],

    // (опционально) аккуратная вертикальная выравниловка и отступы
    'vue/html-indent': ['error', 2, {
      baseIndent: 1,
      attribute: 1,
      closeBracket: 0,
      alignAttributesVertically: true,
    }],

    'antfu/if-newline': 'off',
  },
})
```
- **Порядок импортов** отдаем на откуп `lint:fix`:
  ```jsonc
  { "scripts": { "lint:fix": "eslint --fix --ext .js,.ts,.vue ./resources" } }
  ```

---

## 5) Интеграция с Inertia (детали)

- Ссылки и переходы — через Inertia (`<Link>`, `router.visit`, `useForm`) вместо ручных `window.location`.
- Для `<UButton>` + `<Link>` используем слот/обёртку компонентой‑адаптером, чтобы не терять стили.
- Валидация → pending‑состояние → сабмит → обработка ошибок сервера → тост.

---

## 6) Производительность и a11y

- Тяжёлые блоки — через `defineAsyncComponent` и ленивый маунт по пересечению.
- Минимизируем `watch`, предпочитаем `computed` без побочек.
- Доступность: правильные роли (`UButton`/`ULink`), фокус‑ловушка в модалках, `Esc` закрывает оверлеи.

---

## 7) MUST / MUST NOT

**MUST**
- `ui({ inertia: true })` в Vite и подключённый `@nuxt/ui/vue-plugin` в Inertia‑энтрипойнте.
- `<UApp>` на корне дерева компонентов; `class="isolate"` на корневом контейнере Blade.
- Компоненты библиотеки вместо «ручной» разметки (`UCard`, `UButton`, `UForm` и т.д.).
- Programmatic overlays **через проектные composables** (например, `useContact()`), а не прямые вызовы `useOverlay()` в страницах.
- Везде использовать **bun** (install/dev/build).
- ESLint‑конфиг как выше; порядок импортов — через `lint:fix`.
- MCP‑границы: любые команды выполняются внутри контейнера через MCP; длинные логи — резюмировать.

**MUST NOT**
- Не предлагать Nuxt‑модули/`nuxt.config.ts` — у нас Vue/Vite‑режим (без Nuxt).
- Не писать «карточки/модалки/кнопки» руками из `div` + классы; использовать компоненты Nuxt UI.
- Не импортировать `useToast`/`useOverlay` прямо в страницах — только через проектные composables.
- Не дергать `docker compose exec ...`, `docker.sock`, не просить отключать песочницу.

---

## 8) Примеры паттернов

### 8.1 Страница
```vue
<script setup lang="ts">
function createPlan() { /* ... */ }
</script>

<template>
  <UPage>
    <UPageHeader
      :title="$t('billing.title')"
      :description="$t('billing.subtitle')"
    >
      <template #actions>
        <UButton icon="i-lucide-plus" @click="createPlan">
          {{ $t('billing.create') }}
        </UButton>
      </template>
    </UPageHeader>

    <USection>
      <UPageCard>
        <!-- content -->
      </UPageCard>
    </USection>
  </UPage>
</template>
```

### 8.2 Модалка (programmatic)
`resources/js/components/modals/ContactModal.vue`:
```vue
<script setup lang="ts">
const props = defineProps<{ title?: string }>()

const emit = defineEmits<{ (e: 'close'): void }>()
function close() { emit('close') }
</script>

<template>
  <UModal>
    <UCard>
      <template #header>{{ props.title }}</template>
      <!-- form -->
      <template #footer>
        <UButton variant="ghost" @click="close">Закрыть</UButton>
        <UButton color="primary">Отправить</UButton>
      </template>
    </UCard>
  </UModal>
</template>
```

Открытие — см. `useContact()` выше и `@click="openModal"` на триггере.

### 8.3 Empty/Skeleton
```vue
<template>
  <UCard v-if="pending">
    <USkeleton class="h-6 w-40" />
    <USkeleton class="h-4 w-full mt-2" />
  </UCard>

  <UEmpty
    v-else-if="!items.length"
    icon="i-lucide-package-open"
    :description="$t('common.empty')" />

  <UCard v-else>
    <!-- content -->
  </UCard>
</template>
```

---

## 9) Быстрый старт (TL;DR)

- Vite: `ui({ inertia: true })` + `@nuxt/ui/vue-plugin`.
- CSS: `@import "tailwindcss"; @import "@nuxt/ui";` и импорт в `app.ts`.
- Корень: `<UApp>` и `class="isolate"` на контейнере.
- Programmatic overlays: через composables (`useContact()` и др.).
- Компоненты библиотеки вместо «самописных» блоков.
- bun: `bun add`, `bun run dev`, `bun run build`.
- Линт: проектный ESLint из раздела 4.1, `lint:fix` правит импорты.

