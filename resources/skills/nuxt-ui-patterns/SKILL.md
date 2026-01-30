---
name: nuxt-ui-patterns
description: 'Паттерны Nuxt UI (Laravel + Inertia): overlays, формы, темизация, конвенции.'
---

# Skill: Nuxt UI — Паттерны, темизация, конвенции (Laravel + Inertia)

**Версия:** 2026‑01‑30

**Когда использовать:**
- Строишь UI на Nuxt UI в Laravel + Inertia.
- Нужны паттерны страниц (`UPage`), формы, таблицы, тосты, overlay‑модалки/слайдоверы.
- Нужны конвенции кода, a11y, перфоманс.

> Для API компонентов сначала используй MCP (skill `nuxt-ui-mcp-and-docs`).
> Базовая интеграция (Vite / UApp / CSS) — skill `nuxt-ui-integration`.

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

- Сабмит через `@inertiajs/vue3` (`router.post/put/delete` или `useForm`), ошибки сервера маппим в поля.
- Кнопки сабмита — с `:loading`/`disabled` на pending.


### 2.5 Модалки: сдвиг страницы из‑за scroll‑lock (MUST)

**Симптом:** при открытии `UModal` пропадает полоса прокрутки, фон «прыгает» из‑за добавленного `padding-right` на `body`.

**Причина:** Reka UI при блокировке скролла компенсирует ширину скроллбара через `body` padding/margin.

**Решение (обязательное для фронта):**

1) Обернуть сайт в `UApp` и отключить компенсацию padding/margin:

```vue
<template>
  <UApp :scroll-body="{ padding: 0, margin: 0 }" :toaster="null">
    <slot />
  </UApp>
</template>
```

2) Зафиксировать видимый скроллбар на фронте (без «гаттера»):

```scss
html.site-scroll {
  overflow-y: scroll;
}
```

3) Добавить класс `site-scroll` на `<html>` в публичном лейауте:

```ts
const root = document.documentElement
root.classList.add('site-scroll')
```

> **Не делаем** кастомные overlay‑слои вместо `UModal` ради скролла — используем стандартный overlay и настраиваем `UApp`.

---

## 3) Темизация

- Базовые цвета/радиусы можно задать прямо в опциях Vite‑плагина `ui({ ui: { colors: {...} } })`.
- Если Tailwind подключён с префиксом (`@import "tailwindcss" prefix(tw);`), в Vite‑плагине Nuxt UI используем тот же префикс: `ui({ theme: { prefix: 'tw' } })`, иначе утилиты в темах компонентов будут собираться без префикса.
- Tailwind v4 — используем только поддерживаемые утилиты; deprecated‑классы не допускаются.

---

## 4) Конвенции кода (Vue)

- Только **`<script setup lang="ts">`** в компонентах.
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
- `ui({ router: 'inertia' })` в Vite и подключённый `@nuxt/ui/vue-plugin` в Inertia‑энтрипойнте.
- `<UApp>` на корне дерева компонентов; `class="isolate"` на корневом контейнере Blade.
- Компоненты библиотеки вместо «ручной» разметки (`UCard`, `UButton`, `UForm` и т.д.).
- Programmatic overlays **через проектные composables** (например, `useContact()`), а не прямые вызовы `useOverlay()` в страницах.
- Везде использовать **bun** (install/dev/build).
- ESLint‑конфиг как выше; порядок импортов — через `lint:fix`.
- Песочница/контейнер/логи: см. `.ai/guidelines/01-core.md`.

**MUST NOT**
- Не предлагать Nuxt‑модули/`nuxt.config.ts` — у нас Vue/Vite‑режим (без Nuxt).
- Не писать «карточки/модалки/кнопки» руками из `div` + классы; использовать компоненты Nuxt UI.
- Не импортировать `useToast`/`useOverlay` прямо в страницах — только через проектные composables.
- Песочница/контейнер: см. `.ai/guidelines/01-core.md`.

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

- Vite: `ui({ router: 'inertia' })` + `@nuxt/ui/vue-plugin`.
- CSS: `@import "tailwindcss"; @import "@nuxt/ui";` и импорт в `app.ts`.
- Корень: `<UApp>` и `class="isolate"` на контейнере.
- Programmatic overlays: через composables (`useContact()` и др.).
- Компоненты библиотеки вместо «самописных» блоков.
- bun: `bun add`, `bun run dev`, `bun run build`.
- Линт: проектный ESLint из раздела 4.1, `lint:fix` правит импорты. `typecheck` для проверки типов.
