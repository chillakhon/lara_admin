# Max Messenger Integration - Frontend Documentation

## 📋 Обзор

Интеграция Max Messenger позволяет принимать и отправлять сообщения через Max Bot API. Все настройки управляются через REST API.

---

## 🔐 Аутентификация

Все admin endpoints требуют аутентификации через `auth:sanctum`.

**Headers:**
```
Authorization: Bearer {your_token}
Content-Type: application/json
Accept: application/json
```

---

## 🌐 API Endpoints

### 1. Получить текущие настройки

**GET** `/api/third-party-integrations/max/settings`

**Response 200:**
```json
{
  "id": 1,
  "is_active": true,
  "created_at": "2026-04-09T10:00:00.000000Z",
  "updated_at": "2026-04-09T10:00:00.000000Z"
}
```

**Response 200 (если настроек нет):**
```json
null
```

---

### 2. Сохранить настройки (+ автоматическая регистрация webhook)

**POST** `/api/third-party-integrations/max/settings`

**Request Body:**
```json
{
  "bot_token": "your_bot_token_from_masterbot",
  "is_active": true
}
```

**Validation Rules:**
- `bot_token` - required, string
- `is_active` - optional, boolean (default: true)

**Response 200 (успешно):**
```json
{
  "settings": {
    "id": 1,
    "is_active": true,
    "created_at": "2026-04-09T10:00:00.000000Z",
    "updated_at": "2026-04-09T10:00:00.000000Z"
  },
  "webhook": {
    "success": true,
    "message": "Webhook registered successfully",
    "url": "https://your-domain.com/api/max/webhook",
    "already_exists": false
  }
}
```

**Response 200 (webhook уже зарегистрирован):**
```json
{
  "settings": {
    "id": 1,
    "is_active": true,
    "created_at": "2026-04-09T10:00:00.000000Z",
    "updated_at": "2026-04-09T10:00:00.000000Z"
  },
  "webhook": {
    "success": true,
    "message": "Webhook already registered",
    "url": "https://your-domain.com/api/max/webhook",
    "already_exists": true
  }
}
```

**Response 422 (ошибка валидации):**
```json
{
  "message": "The bot token field is required.",
  "errors": {
    "bot_token": [
      "The bot token field is required."
    ]
  }
}
```

---

### 3. Тест подключения к Max API

**POST** `/api/third-party-integrations/max/settings/test`

**Response 200 (успешно):**
```json
{
  "success": true,
  "bot_info": {
    "user_id": 258261316,
    "name": "Your Bot Name",
    "username": "your_bot",
    "status": "bot",
    "description": "Bot description",
    "avatar_url": "https://..."
  }
}
```

**Response 400 (ошибка):**
```json
{
  "success": false,
  "error": "Max settings not found"
}
```

**Response 400 (неверный токен):**
```json
{
  "success": false,
  "error": "Invalid bot token"
}
```

---

### 4. Получить URL webhook

**GET** `/api/third-party-integrations/max/webhook/url`

**Response 200:**
```json
{
  "base_url": "https://your-domain.com",
  "full_url": "https://your-domain.com/api/max/webhook",
  "has_secret": true
}
```

**Описание полей:**
- `base_url` - базовый URL из конфига (MAX_WEBHOOK_URL или APP_URL)
- `full_url` - полный URL webhook (base_url + /api/max/webhook)
- `has_secret` - есть ли установленный secret для верификации webhook

---

### 5. Получить список активных webhook подписок

**GET** `/api/third-party-integrations/max/webhook/subscriptions`

**Response 200:**
```json
{
  "subscriptions": [
    {
      "url": "https://your-domain.com/api/max/webhook",
      "update_types": [
        "message_created",
        "message_edited",
        "message_deleted",
        "message_read",
        "bot_started"
      ]
    }
  ],
  "current_url": "https://your-domain.com/api/max/webhook"
}
```

**Response 200 (нет подписок):**
```json
{
  "subscriptions": [],
  "current_url": "https://your-domain.com/api/max/webhook"
}
```

---

### 6. Удалить webhook подписку

**POST** `/api/third-party-integrations/max/webhook/unregister`

**Response 200 (успешно):**
```json
{
  "success": true,
  "result": {
    "ok": true
  }
}
```

**Response 200 (ошибка):**
```json
{
  "success": false,
  "error": "Subscription not found"
}
```

---

### 7. Перерегистрировать webhook

**POST** `/api/third-party-integrations/max/webhook/reregister`

Удаляет существующую подписку и создает новую.

**Response 200:**
```json
{
  "success": true,
  "message": "Webhook registered successfully",
  "url": "https://your-domain.com/api/max/webhook",
  "already_exists": false
}
```

---

## 🎨 UI/UX Рекомендации

### Страница настроек Max Integration

**Компоненты:**

1. **Форма настроек:**
   - Input для `bot_token` (type: password, с кнопкой показать/скрыть)
   - Toggle для `is_active`
   - Кнопка "Сохранить настройки"
   - Кнопка "Тест подключения"

2. **Статус webhook:**
   - Индикатор статуса (зарегистрирован/не зарегистрирован)
   - Отображение URL webhook
   - Кнопка "Перерегистрировать webhook"
   - Кнопка "Удалить webhook"

3. **Список активных подписок:**
   - Таблица с URL и типами событий
   - Возможность удаления каждой подписки

### Пример UI Flow:

```
┌─────────────────────────────────────────────────────────────┐
│  Max Messenger Integration                                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Bot Token: [••••••••••••••••••••••] [👁]                  │
│                                                             │
│  Active: [✓] Включить интеграцию                           │
│                                                             │
│  [Сохранить настройки]  [Тест подключения]                 │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  Webhook Status                                             │
│                                                             │
│  Status: ✅ Зарегистрирован                                 │
│  URL: https://your-domain.com/api/max/webhook              │
│                                                             │
│  [Перерегистрировать]  [Удалить]                           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  Active Subscriptions (1)                                   │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ URL: https://your-domain.com/api/max/webhook         │ │
│  │ Events: message_created, message_edited, ...         │ │
│  │                                          [Удалить]    │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📝 Примеры кода для фронтенда

### React/TypeScript Example

```typescript
import { useState } from 'react';
import axios from 'axios';

interface MaxSettings {
  id: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface WebhookResponse {
  success: boolean;
  message: string;
  url: string;
  already_exists: boolean;
}

interface SaveSettingsResponse {
  settings: MaxSettings;
  webhook: WebhookResponse;
}

const MaxIntegrationSettings = () => {
  const [botToken, setBotToken] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Загрузка текущих настроек
  const loadSettings = async () => {
    try {
      const response = await axios.get('/api/third-party-integrations/max/settings');
      if (response.data) {
        setIsActive(response.data.is_active);
      }
    } catch (err) {
      console.error('Failed to load settings', err);
    }
  };

  // Сохранение настроек
  const saveSettings = async () => {
    setLoading(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await axios.post<SaveSettingsResponse>(
        '/api/third-party-integrations/max/settings',
        {
          bot_token: botToken,
          is_active: isActive,
        }
      );

      const { settings, webhook } = response.data;

      if (webhook.success) {
        if (webhook.already_exists) {
          setSuccess('Настройки сохранены. Webhook уже был зарегистрирован.');
        } else {
          setSuccess(`Настройки сохранены. Webhook зарегистрирован: ${webhook.url}`);
        }
      } else {
        setError('Настройки сохранены, но не удалось зарегистрировать webhook.');
      }
    } catch (err: any) {
      if (err.response?.data?.errors) {
        setError(Object.values(err.response.data.errors).flat().join(', '));
      } else {
        setError('Ошибка при сохранении настроек');
      }
    } finally {
      setLoading(false);
    }
  };

  // Тест подключения
  const testConnection = async () => {
    setLoading(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await axios.post('/api/third-party-integrations/max/settings/test');
      
      if (response.data.success) {
        setSuccess(`Подключение успешно! Бот: ${response.data.bot_info.name}`);
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Ошибка подключения к Max API');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-integration-settings">
      <h2>Max Messenger Integration</h2>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <div className="form-group">
        <label>Bot Token:</label>
        <input
          type="password"
          value={botToken}
          onChange={(e) => setBotToken(e.target.value)}
          placeholder="Введите токен бота от @MasterBot"
        />
      </div>

      <div className="form-group">
        <label>
          <input
            type="checkbox"
            checked={isActive}
            onChange={(e) => setIsActive(e.target.checked)}
          />
          Активировать интеграцию
        </label>
      </div>

      <div className="button-group">
        <button onClick={saveSettings} disabled={loading || !botToken}>
          {loading ? 'Сохранение...' : 'Сохранить настройки'}
        </button>
        <button onClick={testConnection} disabled={loading}>
          Тест подключения
        </button>
      </div>
    </div>
  );
};

export default MaxIntegrationSettings;
```

### Vue 3 Example

```vue
<template>
  <div class="max-integration-settings">
    <h2>Max Messenger Integration</h2>

    <div v-if="error" class="alert alert-error">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="form-group">
      <label>Bot Token:</label>
      <input
        v-model="botToken"
        type="password"
        placeholder="Введите токен бота от @MasterBot"
      />
    </div>

    <div class="form-group">
      <label>
        <input v-model="isActive" type="checkbox" />
        Активировать интеграцию
      </label>
    </div>

    <div class="button-group">
      <button @click="saveSettings" :disabled="loading || !botToken">
        {{ loading ? 'Сохранение...' : 'Сохранить настройки' }}
      </button>
      <button @click="testConnection" :disabled="loading">
        Тест подключения
      </button>
    </div>

    <div v-if="webhookInfo" class="webhook-info">
      <h3>Webhook Status</h3>
      <p>URL: {{ webhookInfo.url }}</p>
      <button @click="reregisterWebhook">Перерегистрировать</button>
      <button @click="unregisterWebhook">Удалить</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

const botToken = ref('');
const isActive = ref(true);
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);
const webhookInfo = ref<any>(null);

const loadSettings = async () => {
  try {
    const response = await axios.get('/api/third-party-integrations/max/settings');
    if (response.data) {
      isActive.value = response.data.is_active;
    }
  } catch (err) {
    console.error('Failed to load settings', err);
  }
};

const saveSettings = async () => {
  loading.value = true;
  error.value = null;
  success.value = null;

  try {
    const response = await axios.post('/api/third-party-integrations/max/settings', {
      bot_token: botToken.value,
      is_active: isActive.value,
    });

    const { webhook } = response.data;

    if (webhook.success) {
      success.value = webhook.already_exists
        ? 'Настройки сохранены. Webhook уже был зарегистрирован.'
        : `Настройки сохранены. Webhook зарегистрирован: ${webhook.url}`;
      
      webhookInfo.value = webhook;
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при сохранении настроек';
  } finally {
    loading.value = false;
  }
};

const testConnection = async () => {
  loading.value = true;
  error.value = null;
  success.value = null;

  try {
    const response = await axios.post('/api/third-party-integrations/max/settings/test');
    
    if (response.data.success) {
      success.value = `Подключение успешно! Бот: ${response.data.bot_info.name}`;
    }
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Ошибка подключения к Max API';
  } finally {
    loading.value = false;
  }
};

const reregisterWebhook = async () => {
  try {
    const response = await axios.post('/api/third-party-integrations/max/webhook/reregister');
    success.value = 'Webhook успешно перерегистрирован';
  } catch (err) {
    error.value = 'Ошибка при перерегистрации webhook';
  }
};

const unregisterWebhook = async () => {
  try {
    await axios.post('/api/third-party-integrations/max/webhook/unregister');
    success.value = 'Webhook успешно удален';
    webhookInfo.value = null;
  } catch (err) {
    error.value = 'Ошибка при удалении webhook';
  }
};

onMounted(() => {
  loadSettings();
});
</script>
```

---

## 🔔 Уведомления для пользователя

### Сценарии уведомлений:

1. **Успешное сохранение настроек:**
   - ✅ "Настройки Max Messenger сохранены. Webhook зарегистрирован."

2. **Webhook уже существует:**
   - ℹ️ "Настройки сохранены. Webhook уже был зарегистрирован ранее."

3. **Успешный тест подключения:**
   - ✅ "Подключение к Max API успешно! Бот: {bot_name}"

4. **Ошибка подключения:**
   - ❌ "Не удалось подключиться к Max API. Проверьте токен бота."

5. **Ошибка валидации:**
   - ⚠️ "Токен бота обязателен для заполнения."

6. **Webhook перерегистрирован:**
   - ✅ "Webhook успешно перерегистрирован."

7. **Webhook удален:**
   - ✅ "Webhook успешно удален."

---

## 🔒 Безопасность

1. **Токен бота:**
   - Всегда используйте `type="password"` для input
   - Не отображайте токен в логах или консоли
   - Токен скрыт в API responses (hidden в модели)

2. **Webhook Secret:**
   - Опциональный параметр для дополнительной безопасности
   - Настраивается через .env (MAX_WEBHOOK_SECRET)

---

## 📊 Мониторинг и отладка

### Проверка статуса интеграции:

1. Загрузить настройки: `GET /api/third-party-integrations/max/settings`
2. Проверить webhook: `GET /api/third-party-integrations/max/webhook/subscriptions`
3. Тест подключения: `POST /api/third-party-integrations/max/settings/test`

### Логи:

Все операции логируются в Laravel logs:
- Входящие webhook события
- Ошибки обработки
- Регистрация/удаление webhook

---

## ❓ FAQ

**Q: Где получить токен бота?**
A: Откройте диалог с @MasterBot в Max Messenger и следуйте инструкциям для создания бота.

**Q: Что делать если webhook не регистрируется?**
A: 
1. Проверьте, что MAX_WEBHOOK_URL в .env указывает на доступный публичный URL
2. Убедитесь, что токен бота корректный
3. Проверьте логи Laravel для деталей ошибки

**Q: Можно ли использовать несколько ботов?**
A: Текущая реализация поддерживает один бот. Для нескольких ботов нужно расширить модель.

**Q: Как проверить, что webhook работает?**
A: Отправьте сообщение боту в Max Messenger и проверьте, что оно появилось в системе Conversations.

---

## 🚀 Быстрый старт для фронтенда

1. Создайте страницу настроек Max Integration
2. Добавьте форму с полями `bot_token` и `is_active`
3. При сохранении вызывайте `POST /api/third-party-integrations/max/settings`
4. Отобразите результат регистрации webhook
5. Добавьте кнопку "Тест подключения"
6. Готово! Webhook автоматически зарегистрируется при сохранении

---

**Дата создания:** 2026-04-09  
**Версия API:** 1.0  
**Пакет:** bushlanov-dev/max-bot-api-client-php v1.6
