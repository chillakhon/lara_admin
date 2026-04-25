# Max Messenger Integration

Полная интеграция Max Messenger Bot API для Laravel приложения.

## 📋 Содержание

- [Обзор](#обзор)
- [Требования](#требования)
- [Установка](#установка)
- [Конфигурация](#конфигурация)
- [Использование](#использование)
- [API Endpoints](#api-endpoints)
- [Документация](#документация)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Обзор

Интеграция позволяет:
- ✅ Принимать сообщения от пользователей Max Messenger
- ✅ Отправлять сообщения через Max Bot API
- ✅ Автоматически регистрировать webhook
- ✅ Обрабатывать все типы вложений (фото, файлы, видео, аудио)
- ✅ Интегрироваться с системой Conversations
- ✅ Связывать пользователей Max с профилями

---

## 📦 Требования

- PHP 8.2+
- Laravel 11.x
- MySQL/PostgreSQL
- Пакет `bushlanov-dev/max-bot-api-client-php` (уже установлен)

---

## 🚀 Установка

### 1. Запустить миграции

```bash
php artisan migrate
```

Будут созданы:
- Таблица `max_settings` для хранения токена бота
- Поле `max_user_id` в таблице `user_profiles`
- Добавлен `max` в enum `conversations.source`

### 2. Настроить .env

```env
MAX_BOT_TOKEN=your_bot_token_from_masterbot
MAX_WEBHOOK_URL=https://your-domain.com
MAX_WEBHOOK_SECRET=optional_secret_key
```

**Важно:**
- `MAX_WEBHOOK_URL` - базовый URL без `/api/max/webhook` (добавляется автоматически)
- Если `MAX_WEBHOOK_URL` не указан, используется `APP_URL`
- `MAX_WEBHOOK_SECRET` - опциональный параметр для дополнительной безопасности

### 3. Получить токен бота

1. Откройте Max Messenger
2. Найдите @MasterBot
3. Отправьте команду `/newbot`
4. Следуйте инструкциям
5. Скопируйте полученный токен

---

## ⚙️ Конфигурация

### Сохранение настроек через API

```bash
POST /api/third-party-integrations/max/settings
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "bot_token": "your_bot_token",
  "is_active": true
}
```

**Webhook регистрируется автоматически!**

Response:
```json
{
  "settings": {
    "id": 1,
    "is_active": true
  },
  "webhook": {
    "success": true,
    "message": "Webhook registered successfully",
    "url": "https://your-domain.com/api/max/webhook",
    "already_exists": false
  }
}
```

---

## 🎮 Использование

### Тест подключения

```bash
POST /api/third-party-integrations/max/settings/test
Authorization: Bearer {your_token}
```

Response:
```json
{
  "success": true,
  "bot_info": {
    "user_id": 258261316,
    "name": "Your Bot Name",
    "username": "your_bot"
  }
}
```

### Проверка webhook подписок

```bash
GET /api/third-party-integrations/max/webhook/subscriptions
Authorization: Bearer {your_token}
```

Response:
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

### Отправка сообщения (программно)

```php
use App\Services\Max\MaxService;

$maxService = app(MaxService::class);

// Отправить сообщение пользователю
$result = $maxService->sendMessage(
    userId: 251011975,
    text: 'Привет! Это сообщение от бота.'
);
```

---

## 🌐 API Endpoints

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/public/max/webhook` | Прием webhook от Max API |

### Admin Endpoints (требуют auth:sanctum)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/third-party-integrations/max/settings` | Получить настройки |
| POST | `/api/third-party-integrations/max/settings` | Сохранить настройки + регистрация webhook |
| POST | `/api/third-party-integrations/max/settings/test` | Тест подключения к Max API |
| GET | `/api/third-party-integrations/max/webhook/url` | Получить URL webhook |
| GET | `/api/third-party-integrations/max/webhook/subscriptions` | Список активных подписок |
| POST | `/api/third-party-integrations/max/webhook/unregister` | Удалить webhook |
| POST | `/api/third-party-integrations/max/webhook/reregister` | Перерегистрировать webhook |

---

## 📚 Документация

### Для разработчиков фронтенда

- **[MAX_INTEGRATION_FRONTEND_DOCS.md](./MAX_INTEGRATION_FRONTEND_DOCS.md)** - Полная документация с примерами кода для React, Vue, cURL, Fetch, Axios
- **[MAX_INTEGRATION_QUICK_REFERENCE.md](./MAX_INTEGRATION_QUICK_REFERENCE.md)** - Быстрая справка и шпаргалка

### Структура проекта

```
app/
├── Models/
│   └── MaxSettings.php                    # Модель настроек
├── Services/
│   └── Max/
│       └── MaxService.php                 # Основная бизнес-логика
└── Http/Controllers/Api/Admin/ThirdPartyIntegrations/Max/
    ├── MaxWebhookController.php           # Обработка webhook
    └── MaxSettingsController.php          # Управление настройками

database/migrations/
├── 2026_04_09_140910_create_max_settings_table.php
├── 2026_04_09_140932_add_max_user_id_to_user_profiles.php
└── 2026_04_09_140951_add_max_to_conversations_source.php
```

---

## 🔧 Troubleshooting

### Webhook не регистрируется

**Проблема:** При сохранении настроек webhook не регистрируется.

**Решение:**
1. Проверьте, что `MAX_WEBHOOK_URL` в `.env` указывает на публично доступный URL
2. Убедитесь, что токен бота корректный
3. Проверьте логи: `tail -f storage/logs/laravel.log`

### Сообщения не приходят

**Проблема:** Отправляю сообщение боту, но оно не появляется в Conversations.

**Решение:**
1. Проверьте, что webhook зарегистрирован: `GET /api/third-party-integrations/max/webhook/subscriptions`
2. Убедитесь, что `is_active = true` в настройках
3. Проверьте логи Laravel на наличие ошибок
4. Убедитесь, что URL webhook доступен извне (не localhost)

### Ошибка "Max settings not found"

**Проблема:** При вызове API получаю ошибку "Max settings not found".

**Решение:**
1. Сначала сохраните настройки через `POST /api/third-party-integrations/max/settings`
2. Убедитесь, что в БД есть запись в таблице `max_settings`

### Файлы не скачиваются

**Проблема:** Вложения в сообщениях не сохраняются.

**Решение:**
1. Проверьте права доступа к директории `storage/app/public/chat-attachments`
2. Убедитесь, что создан symlink: `php artisan storage:link`
3. Проверьте логи на наличие ошибок при скачивании

### Дубликаты webhook

**Проблема:** Webhook регистрируется несколько раз.

**Решение:**
- Интеграция автоматически проверяет существующие подписки
- Если webhook уже зарегистрирован, новый не создается
- Для принудительной перерегистрации используйте: `POST /api/third-party-integrations/max/webhook/reregister`

---

## 🔐 Безопасность

1. **Токен бота:**
   - Храните токен в `.env`, никогда не коммитьте в git
   - Токен скрыт в API responses (hidden в модели)

2. **Webhook Secret:**
   - Используйте `MAX_WEBHOOK_SECRET` для дополнительной защиты
   - Max будет подписывать запросы этим ключом

3. **Аутентификация:**
   - Все admin endpoints требуют `auth:sanctum`
   - Public webhook endpoint доступен без аутентификации (для Max API)

---

## 📊 Мониторинг

### Логирование

Все операции логируются в `storage/logs/laravel.log`:
- Входящие webhook события
- Ошибки обработки сообщений
- Регистрация/удаление webhook
- Скачивание файлов

### Просмотр логов

```bash
# Все логи Max интеграции
tail -f storage/logs/laravel.log | grep "Max"

# Только ошибки
tail -f storage/logs/laravel.log | grep "MaxService: Exception"

# Входящие webhook
tail -f storage/logs/laravel.log | grep "Max webhook received"
```

---

## 🎯 Workflow обработки сообщений

```
1. Пользователь отправляет сообщение боту в Max
   ↓
2. Max API отправляет webhook на /api/public/max/webhook
   ↓
3. MaxWebhookController получает данные
   ↓
4. MaxService.handleWebhookUpdate() обрабатывает событие
   ↓
5. Определяется тип события (message_created, edited, etc.)
   ↓
6. Для message_created:
   - Извлекается sender.user_id
   - Ищется Client по max_user_id в user_profiles
   - Создается/обновляется Conversation (source='max')
   - Обрабатываются attachments (скачивание файлов)
   - Сохраняется Message через ConversationService
   ↓
7. Отправляется event ConversationUpdated
   ↓
8. Сообщение появляется в системе Conversations
```

---

## 🔄 Обновление

При обновлении интеграции:

```bash
# Обновить автозагрузку
composer dump-autoload

# Очистить кэш
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Запустить новые миграции (если есть)
php artisan migrate
```

---

## 📝 Changelog

### v1.0.0 (2026-04-09)

**Добавлено:**
- Полная интеграция с Max Bot API
- Автоматическая регистрация webhook
- Обработка всех типов событий
- Поддержка вложений (image, file, video, audio)
- Интеграция с системой Conversations
- Связывание пользователей через max_user_id
- Полная документация для фронтенда

---

## 🤝 Поддержка

- **Документация Max API:** https://dev.max.ru/
- **Пакет:** bushlanov-dev/max-bot-api-client-php v1.6
- **Laravel:** 11.x
- **PHP:** 8.2+

---

## 📄 Лицензия

Интеграция разработана для внутреннего использования в проекте.

---

**Дата создания:** 2026-04-09  
**Версия:** 1.0.0  
**Статус:** Production Ready ✅
