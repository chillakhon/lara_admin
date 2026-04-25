# Max Integration - Quick Reference

## 🚀 Быстрый старт

### 1. Backend Setup
```bash
# Добавить в .env
MAX_BOT_TOKEN=your_token
MAX_WEBHOOK_URL=https://your-domain.com
MAX_WEBHOOK_SECRET=optional_secret

# Запустить миграции
php artisan migrate
```

### 2. Frontend Integration

#### Сохранить настройки (автоматически регистрирует webhook)
```javascript
POST /api/third-party-integrations/max/settings
{
  "bot_token": "your_token",
  "is_active": true
}
```

#### Тест подключения
```javascript
POST /api/third-party-integrations/max/settings/test
```

#### Получить статус webhook
```javascript
GET /api/third-party-integrations/max/webhook/subscriptions
```

---

## 📡 API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/third-party-integrations/max/settings` | ✅ | Получить настройки |
| POST | `/api/third-party-integrations/max/settings` | ✅ | Сохранить + регистрация webhook |
| POST | `/api/third-party-integrations/max/settings/test` | ✅ | Тест подключения |
| GET | `/api/third-party-integrations/max/webhook/url` | ✅ | URL webhook |
| GET | `/api/third-party-integrations/max/webhook/subscriptions` | ✅ | Список подписок |
| POST | `/api/third-party-integrations/max/webhook/unregister` | ✅ | Удалить webhook |
| POST | `/api/third-party-integrations/max/webhook/reregister` | ✅ | Перерегистрировать |
| POST | `/api/public/max/webhook` | ❌ | Прием webhook от Max |

---

## 💡 Примеры запросов

### cURL

```bash
# Сохранить настройки
curl -X POST https://your-domain.com/api/third-party-integrations/max/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"bot_token":"YOUR_BOT_TOKEN","is_active":true}'

# Тест подключения
curl -X POST https://your-domain.com/api/third-party-integrations/max/settings/test \
  -H "Authorization: Bearer YOUR_TOKEN"

# Получить подписки
curl -X GET https://your-domain.com/api/third-party-integrations/max/webhook/subscriptions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### JavaScript (Fetch)

```javascript
// Сохранить настройки
const response = await fetch('/api/third-party-integrations/max/settings', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    bot_token: 'YOUR_BOT_TOKEN',
    is_active: true
  })
});

const data = await response.json();
console.log(data.webhook.url); // Webhook URL
```

### Axios

```javascript
// Сохранить настройки
const { data } = await axios.post('/api/third-party-integrations/max/settings', {
  bot_token: 'YOUR_BOT_TOKEN',
  is_active: true
});

console.log(data.webhook.message); // "Webhook registered successfully"
```

---

## 📋 Response Examples

### Успешное сохранение
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

### Успешный тест
```json
{
  "success": true,
  "bot_info": {
    "user_id": 258261316,
    "name": "Your Bot Name",
    "username": "your_bot",
    "status": "bot"
  }
}
```

### Список подписок
```json
{
  "subscriptions": [
    {
      "url": "https://your-domain.com/api/max/webhook",
      "update_types": ["message_created", "message_edited", ...]
    }
  ],
  "current_url": "https://your-domain.com/api/max/webhook"
}
```

---

## ⚠️ Обработка ошибок

```javascript
try {
  const response = await axios.post('/api/third-party-integrations/max/settings', data);
  // Успех
} catch (error) {
  if (error.response?.status === 422) {
    // Ошибка валидации
    console.error(error.response.data.errors);
  } else if (error.response?.status === 400) {
    // Ошибка API
    console.error(error.response.data.error);
  } else {
    // Другие ошибки
    console.error('Unexpected error');
  }
}
```

---

## 🔑 Получение токена бота

1. Откройте Max Messenger
2. Найдите @MasterBot
3. Отправьте команду `/newbot`
4. Следуйте инструкциям
5. Скопируйте полученный токен

---

## ✅ Checklist для интеграции

- [ ] Добавлены переменные в .env
- [ ] Запущены миграции
- [ ] Получен токен от @MasterBot
- [ ] Создана страница настроек на фронте
- [ ] Реализовано сохранение настроек
- [ ] Добавлена кнопка "Тест подключения"
- [ ] Отображается статус webhook
- [ ] Протестирована отправка сообщения боту
- [ ] Сообщение появилось в Conversations

---

## 🐛 Troubleshooting

**Webhook не регистрируется:**
- Проверьте MAX_WEBHOOK_URL в .env
- URL должен быть публично доступен
- Проверьте токен бота

**Сообщения не приходят:**
- Проверьте логи Laravel: `tail -f storage/logs/laravel.log`
- Убедитесь что webhook зарегистрирован
- Проверьте что бот активен

**Ошибка "Max settings not found":**
- Сначала сохраните настройки через API
- Убедитесь что is_active = true

---

## 📞 Support

- Документация Max API: https://dev.max.ru/
- Пакет: bushlanov-dev/max-bot-api-client-php
- Полная документация: MAX_INTEGRATION_FRONTEND_DOCS.md

