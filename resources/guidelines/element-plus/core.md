# AI Guidelines: Element Plus + Vue 3

> Этот файл читают AI‑ассистенты (Cursor, Claude, Copilot Chat и т.п.).
> Всегда следуй этим правилам, когда генерируешь фронтенд‑код для этого проекта.

## 1. Назначение и границы

- UI строится на **Vue 3** и **Element Plus**.
- Нельзя использовать другие UI‑фреймворки (Vuetify, Bootstrap, Naive UI и т.п.), если это явно не указано в задаче.
- Все формы, таблицы, модальные окна, уведомления и т.п. должны использовать компоненты Element Plus, если это возможно.

Если существующий код проекта противоречит этому гайду, **приоритет за существующим кодом**.

---

## 2. Технологический контекст

- Бандлер: современный (обычно Vite или аналог).
- Компоненты пишем на Vue 3 с `<script setup>` и Composition API.
- Разрешён TypeScript (`lang="ts"`), его стоит предпочитать, если проект уже типизирован.
- Импорт компонентов — в стиле ES‑модулей.

---

## 3. Установка и подключение Element Plus

### 3.1. Установка

Для установки Element Plus используй пакетный менеджер:

```bash
npm install element-plus
# или
yarn add element-plus
# или
pnpm add element-plus
```

### 3.2. Подключение (full import по умолчанию)

Точка входа в приложение (например, `main.ts` или `app.ts`) должна подключать Element Plus глобально:

```ts
import { createApp } from 'vue'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import App from './App.vue'

const app = createApp(App)

app.use(ElementPlus)
app.mount('#app')
```

Если в проекте уже настроен on‑demand / auto‑import:

- **не добавляй ручной full import**,
- следуй существующей конфигурации (ищи плагины вроде `@element-plus/nuxt`, `unplugin-vue-components` и т.п.).

### 3.3. Глобальная конфигурация компонентов

Глобальные опции Element Plus (например, `size` и `zIndex`) настраиваются при подключении:

```ts
app.use(ElementPlus, {
  size: 'default', // 'small' | 'large' и т.п.
  zIndex: 3000,
})
```

Если в проекте уже определены значения — не меняй их без явной просьбы.

---

## 4. Layout: базовый каркас страниц

Для базовой структуры страницы используй контейнеры Element Plus:

- `<el-container>` — корневой контейнер.
- `<el-header>` — верхняя панель.
- `<el-aside>` — боковая панель (навигация).
- `<el-main>` — основное содержимое.
- `<el-footer>` — нижняя панель.

### 4.1. Типичный layout

```vue
<template>
  <el-container class="layout">
    <el-header class="layout__header">
      <!-- шапка приложения -->
    </el-header>

    <el-container>
      <el-aside width="240px" class="layout__aside">
        <!-- меню / навигация -->
      </el-aside>

      <el-main class="layout__main">
        <slot />
      </el-main>
    </el-container>
  </el-container>
</template>
```

Правила:

- Не используй случайные `<div class="header">` / `<div class="sidebar">`, если можно использовать контейнеры Element Plus.
- Поддерживай единый layout‑паттерн по всему приложению.

---

## 5. Формы

Общие правила:

- Для бизнес‑форм всегда используй `<el-form>` + `<el-form-item>`.
- Связка:
  - `:model="form"` — реактивный объект с данными формы.
  - `:rules="rules"` — объект с правилами валидации (если валидация нужна).
  - `ref="formRef"` — ссылка для вызова `formRef.value.validate()`.
- Поля формы — компоненты Element Plus:
  - текст: `el-input`,
  - числа: `el-input-number`,
  - выбор: `el-select`, `el-radio-group`, `el-checkbox-group`,
  - даты: `el-date-picker`, `el-time-picker` и т.п.

### 5.1. Базовый пример формы

```vue
<template>
  <el-form
    ref="formRef"
    :model="form"
    :rules="rules"
    label-position="top"
  >
    <el-form-item label="Название" prop="name">
      <el-input v-model="form.name" placeholder="Введите название" />
    </el-form-item>

    <el-form-item label="Статус" prop="status">
      <el-select v-model="form.status" placeholder="Выберите статус">
        <el-option label="Активен" value="active" />
        <el-option label="Неактивен" value="inactive" />
      </el-select>
    </el-form-item>

    <el-form-item>
      <el-button type="primary" @click="onSubmit">Сохранить</el-button>
      <el-button @click="onReset">Сбросить</el-button>
    </el-form-item>
  </el-form>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'

interface FormData {
  name: string
  status: 'active' | 'inactive' | ''
}

const formRef = ref<FormInstance>()
const form = ref<FormData>({
  name: '',
  status: '',
})

const rules: FormRules<FormData> = {
  name: [{ required: true, message: 'Введите название', trigger: 'blur' }],
  status: [{ required: true, message: 'Выберите статус', trigger: 'change' }],
}

const onSubmit = async () => {
  if (!formRef.value) return
  await formRef.value.validate((valid) => {
    if (!valid) return
    // отправка данных на сервер
  })
}

const onReset = () => {
  formRef.value?.resetFields()
}
</script>
```

### 5.2. Правила для форм

- Не используй `alert()` / `console.log` для ошибок валидации — ошибки должны отображаться через `rules` и подсветку `el-form-item`.
- При сабмите:
  - сначала вызывай `validate`,
  - только при успешной валидации выполняй запрос к API.
- Обязательные поля помечай `required` с понятными пользователю сообщениями.

---

## 6. Таблицы и списки данных

Для табличных данных используй `<el-table>`:

- `:data="items"` — массив объектов.
- Столбцы описывай через `<el-table-column>`.
- Для удобочитаемости по умолчанию допускается `stripe` и/или `border`.

### 6.1. Базовая таблица с действиями

```vue
<template>
  <el-table
    :data="items"
    stripe
    border
    style="width: 100%"
  >
    <el-table-column prop="id" label="ID" width="80" />
    <el-table-column prop="name" label="Название" min-width="200" />
    <el-table-column prop="status" label="Статус" width="140" />

    <el-table-column label="Действия" width="160">
      <template #default="{ row }">
        <el-button size="small" type="primary" text @click="edit(row)">
          Редактировать
        </el-button>
        <el-button size="small" type="danger" text @click="remove(row)">
          Удалить
        </el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Item {
  id: number
  name: string
  status: string
}

const items = ref<Item[]>([])

const edit = (row: Item) => {
  // открыть форму редактирования
}

const remove = (row: Item) => {
  // запрос на удаление + подтверждение
}
</script>
```

### 6.2. Рекомендации по таблицам

- Не генерируй “сырой” `<table>` для бизнес‑данных — используй `el-table`.
- Кнопки действий держи в последнем столбце.
- Для больших таблиц добавляй пагинацию (`el-pagination`) и/или виртуализацию (`el-table-v2`), если это уже используется в проекте.

---

## 7. Диалоги, тосты и уведомления

### 7.1. Диалоги (`el-dialog`)

- Для модальных окон используй `<el-dialog>`.
- Управляй модалкой через `v-model` / `v-model:visible`.
- Кнопки “Сохранить/Отмена” выноси в `#footer`.

```vue
<template>
  <el-dialog
    v-model="visible"
    title="Создать запись"
    width="500px"
  >
    <!-- форма создания -->

    <template #footer>
      <el-button @click="visible = false">Отмена</el-button>
      <el-button type="primary" @click="onSubmit">Сохранить</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const visible = ref(false)

const onSubmit = () => {
  // логика сохранения
}
</script>
```

### 7.2. Короткие сообщения

Используй методы Element Plus, а не `alert`:

- `ElMessage` — короткие тост‑сообщения (успех/ошибка/инфо).
- `ElNotification` — более подробные уведомления, обычно в углу экрана.
- `ElMessageBox` — модальные confirm/alert диалоги.

```ts
import { ElMessage, ElMessageBox, ElNotification } from 'element-plus'

ElMessage.success('Сохранено успешно')
ElMessage.error('Не удалось сохранить данные')

await ElMessageBox.confirm(
  'Вы действительно хотите удалить запись?',
  'Подтверждение',
  { type: 'warning' },
)

ElNotification({
  title: 'Импорт завершён',
  message: 'Импортировано 120 записей',
  type: 'success',
})
```

Правила:

- Для подтверждения опасных действий (удаление, сброс) используй `ElMessageBox.confirm`.
- Для статуса операций используй `ElMessage` или `ElNotification` (если уведомление должно быть более заметным и долгим).

---

## 8. Иконки

Для иконок используй пакет `@element-plus/icons-vue`.

### 8.1. Локальный импорт в компоненте

```vue
<script setup lang="ts">
import { Edit, Delete } from '@element-plus/icons-vue'
</script>

<template>
  <el-button type="primary">
    <el-icon style="margin-right: 4px">
      <Edit />
    </el-icon>
    Редактировать
  </el-button>

  <el-button type="danger" text>
    <el-icon>
      <Delete />
    </el-icon>
    Удалить
  </el-button>
</template>
```

### 8.2. Глобальная регистрация (опционально)

Если проект регистрирует иконки глобально (например, в `main.ts` через перебор всех иконок), AI‑ассистент должен:

- проверить, есть ли такая регистрация,
- использовать иконки в соответствии с этим способом (без дублирования импорта/регистрации).

---

## 9. Стили, темы и глобальные настройки

- Базовые стили Element Plus подключаются через `element-plus/dist/index.css`.
- Дополнительные стили/темы (dark‑тема, кастомные SCSS‑переменные) настраиваются на уровне конфигурации проекта.
- AI‑ассистент **не должен**:
  - переопределять базовые цвета Element Plus через большие инлайн‑стили,
  - писать CSS, который дублирует стандартное поведение компонентов.

Если нужно изменить внешний вид компонента:

1. В первую очередь используй props компонента (например, `type`, `size`, `plain`, `round` у `el-button`).
2. Затем — utility‑классы проекта (Tailwind/UnoCSS и т.п., если они есть).
3. Только если это невозможно, добавляй scoped‑стили в `<style scoped>`.

---

## 10. Code style и архитектура компонентов

- Используй `<script setup>` + Composition API.
- Имена компонентов — PascalCase.
- Храни UI‑компоненты в каталоге `components/` (или другом, принятом в проекте).
- Разноси бизнес‑логику по composables/сервисам, если это уже сделано в проекте.
- Избегай Options API (`export default { data() { ... } }`), если в проекте в основном используется Composition API.

Асинхронные операции:

- При длительных запросах показывай индикатор загрузки:
  - через `v-loading` (`el-loading`),
  - или через disabled‑состояние кнопки + спиннер, если в проекте так принято.
- Ошибки запросов показывай через `ElMessage.error` или inline‑ошибки в форме.

---

## 11. Типовой экран: список + создание/редактирование

Этот паттерн dùng для CRUD‑экранов:

- Таблица (`el-table`) со списком записей.
- Кнопка “Создать” над таблицей.
- Модальное окно (`el-dialog`) с формой (`el-form`) для создания/редактирования.
- При успешном сохранении:
  - показать `ElMessage.success`,
  - обновить данные таблицы,
  - закрыть диалог.

```vue
<template>
  <div class="page">
    <div class="page__header">
      <el-button type="primary" @click="openCreate">
        Создать запись
      </el-button>
    </div>

    <el-table :data="items" stripe border>
      <el-table-column prop="name" label="Название" />
      <el-table-column prop="status" label="Статус" width="140" />

      <el-table-column label="Действия" width="160">
        <template #default="{ row }">
          <el-button size="small" text type="primary" @click="openEdit(row)">
            Редактировать
          </el-button>
          <el-button size="small" text type="danger" @click="confirmRemove(row)">
            Удалить
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog
      v-model="dialogVisible"
      :title="dialogMode === 'create' ? 'Создать запись' : 'Редактировать запись'"
      width="500px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-position="top"
      >
        <el-form-item label="Название" prop="name">
          <el-input v-model="form.name" />
        </el-form-item>

        <el-form-item label="Статус" prop="status">
          <el-select v-model="form.status" placeholder="Выберите статус">
            <el-option label="Активен" value="active" />
            <el-option label="Неактивен" value="inactive" />
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">Отмена</el-button>
        <el-button type="primary" :loading="saving" @click="submit">
          Сохранить
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'

interface Item {
  id: number
  name: string
  status: 'active' | 'inactive'
}

interface FormData {
  id: number | null
  name: string
  status: 'active' | 'inactive' | ''
}

const items = ref<Item[]>([])
const dialogVisible = ref(false)
const dialogMode = ref<'create' | 'edit'>('create')
const saving = ref(false)

const formRef = ref<FormInstance>()
const form = ref<FormData>({
  id: null,
  name: '',
  status: '',
})

const rules: FormRules<FormData> = {
  name: [{ required: true, message: 'Введите название', trigger: 'blur' }],
  status: [{ required: true, message: 'Выберите статус', trigger: 'change' }],
}

const reload = async () => {
  // подгрузить items с сервера
}

const openCreate = () => {
  dialogMode.value = 'create'
  form.value = { id: null, name: '', status: '' }
  dialogVisible.value = true
}

const openEdit = (item: Item) => {
  dialogMode.value = 'edit'
  form.value = { id: item.id, name: item.name, status: item.status }
  dialogVisible.value = true
}

const submit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    try {
      saving.value = true
      if (dialogMode.value === 'create') {
        // запрос на создание
      } else {
        // запрос на обновление
      }
      await reload()
      dialogVisible.value = false
      ElMessage.success('Сохранено')
    } finally {
      saving.value = false
    }
  })
}

const confirmRemove = async (item: Item) => {
  try {
    await ElMessageBox.confirm(
      'Вы действительно хотите удалить запись?',
      'Подтверждение',
      { type: 'warning' },
    )
    // запрос на удаление
    await reload()
    ElMessage.success('Удалено')
  } catch {
    // отмена
  }
}
</script>
```

---

## 12. Чего делать нельзя

AI‑ассистент НЕ должен:

- генерировать UI на чистом HTML/CSS там, где уже используются компоненты Element Plus;
- подключать дополнительные UI‑библиотеки без явного указания;
- смешивать Options API и Composition API в одном компоненте без необходимости;
- использовать `alert`, `prompt`, `confirm` из браузера для пользовательских сообщений;
- писать инлайн‑стили там, где можно обойтись props компонентов или утилитными классами;
- создавать собственные “костыльные” компоненты, дублирующие существующие компоненты Element Plus (`Modal`, `Toast` и т.п.).

Если существующий код в проекте выглядит иначе, AI‑ассистент должен ориентироваться на **фактические паттерны в кодовой базе**, а не на общие знания.
