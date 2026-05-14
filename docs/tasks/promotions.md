# Задача: Акции (Promotions)

**Статус:** Реализовано  
**Дата:** 2026-04-28

## Описание

Система акций — механизм автоматического применения спецпредложений к заказу. Когда корзина пользователя соответствует условиям акции (наличие товара-триггера, минимальная сумма), ему предлагается подарок (бесплатный товар) или, если акция разрешает, выбор между подарком и промокодом/скидкой.

---

## Бизнес-логика

1. **Товар-триггер** — товар, присутствие которого в корзине активирует акцию
2. **Товар-подарок** — товар, который клиент получает бесплатно при выполнении условий
3. **Минимальная сумма** — акция активируется только если сумма корзины ≥ `min_purchase_amount`
4. **Приоритет** — если подходит несколько акций, берётся с наибольшим `priority`
5. **`allow_promo_codes`**:
   - `false` — клиент может получить только подарок, промокоды и скидки заблокированы
   - `true` — клиент выбирает: подарок **или** промокод/скидка

---

## Таблицы БД

| Таблица | Назначение |
|---------|-----------|
| `promotions` | Основная сущность акции |
| `promotion_trigger_products` | Товары-триггеры (pivot) |
| `promotion_gift_products` | Товары-подарки с количеством (pivot) |
| `promotion_usages` | История применений акции |

### Поля таблицы `promotions`

| Поле | Тип | Описание |
|------|-----|---------|
| `name` | string | Название акции |
| `description` | text | Описание для клиентов |
| `starts_at` | timestamp | Дата начала (nullable) |
| `ends_at` | timestamp | Дата окончания (nullable) |
| `min_purchase_amount` | decimal | Минимальная сумма покупки |
| `allow_promo_codes` | boolean | Разрешены ли промокоды/скидки |
| `is_active` | boolean | Активна ли акция |
| `priority` | integer | Приоритет (чем выше — тем важнее) |
| `max_uses` | integer | Лимит использований (nullable = без лимита) |
| `times_used` | integer | Счётчик использований |

---

## Файлы

### Модели
| Файл | Описание |
|------|---------|
| `app/Models/Promotion.php` | Основная модель. Методы: `isActive()`, `allowsPromoCodes()`, `getStatusAttribute()` |
| `app/Models/PromotionUsage.php` | История применения акции к заказу |

### Сервис
| Файл | Описание |
|------|---------|
| `app/Services/Promotion/PromotionService.php` | Вся бизнес-логика акций |

**Методы `PromotionService`:**
- `findApplicablePromotions(array $cartItems, float $cartTotal)` — находит активные акции подходящие для корзины
- `applyPromotionToOrder(Order, Promotion, $giftProductId, $useDiscountInstead)` — применяет акцию к заказу, добавляет подарочный `order_item` с `price = 0`
- `cancelPromotionFromOrder(Order)` — отменяет акцию, удаляет подарочные позиции
- `canUsePromoCodeWithPromotion(?Promotion)` — проверяет совместимость акции с промокодом
- `getPromotionStats(Promotion)` — статистика использования

### Контроллеры
| Файл | Описание |
|------|---------|
| `app/Http/Controllers/Api/Admin/Promotion/PromotionController.php` | CRUD для админки |
| `app/Http/Controllers/Api/Public/Promotion/PromotionPublicController.php` | Публичные эндпоинты для фронта |

### Валидация
| Файл | Описание |
|------|---------|
| `app/Http/Requests/Order/CreateOrderRequest.php` | Валидация `promotion_id`, `gift_product_id`, `use_discount_instead` |
| `app/Services/Order/OrderValidationService.php` | Проверка совместимости промокода и акции |

### Миграции
| Файл | Описание |
|------|---------|
| `2026_04_11_053025_create_promotions_table.php` | Таблица `promotions` |
| `2026_04_11_053032_create_promotion_trigger_products_table.php` | Таблица триггеров |
| `2026_04_11_053032_create_promotion_gift_products_table.php` | Таблица подарков |
| `2026_04_11_053033_create_promotion_usages_table.php` | История использований |
| `2026_04_11_053041_add_promotion_fields_to_orders_table.php` | `promotion_id` в `orders` |
| `2026_04_11_053041_add_promotion_fields_to_order_items_table.php` | `is_gift`, `promotion_id` в `order_items` |

---

## API Эндпоинты

### Публичные (без авторизации)

| Метод | URL | Описание |
|-------|-----|---------|
| `GET` | `/api/public/promotions` | Список активных акций |
| `POST` | `/api/public/promotions/check-applicable` | Проверить применимые акции для корзины |

**POST `/api/public/promotions/check-applicable`**
```json
// Request
{
  "items": [
    { "product_id": 1, "quantity": 2, "price": 1500.00 }
  ],
  "total": 3000.00
}

// Response
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Купи на 3000₽ — получи подарок",
      "description": "...",
      "allow_promo_codes": false,
      "min_purchase_amount": "3000.00",
      "priority": 10,
      "gift_products": [
        { "id": 5, "name": "Подарочный крем", "price": "500.00", "quantity": 1, "image": "..." }
      ]
    }
  ]
}
```

### Для заказа (auth:sanctum)

При оформлении заказа `POST /api/orders` передаются дополнительные поля:

```json
{
  "promotion_id": 1,
  "gift_product_id": 5,
  "use_discount_instead": false
}
```

| Поле | Обязательность | Описание |
|------|---------------|---------|
| `promotion_id` | nullable | ID применяемой акции |
| `gift_product_id` | обязателен если `promotion_id` есть и `use_discount_instead = false` | ID выбранного подарка |
| `use_discount_instead` | nullable bool | `true` — клиент выбрал промокод/скидку вместо подарка |

### Админские (auth:sanctum)

| Метод | URL | Описание |
|-------|-----|---------|
| `GET` | `/api/promotions` | Список всех акций (с фильтрами) |
| `POST` | `/api/promotions` | Создать акцию |
| `GET` | `/api/promotions/{id}` | Показать акцию + статистика |
| `PUT` | `/api/promotions/{id}` | Обновить акцию |
| `DELETE` | `/api/promotions/{id}` | Удалить акцию (soft delete) |
| `GET` | `/api/promotions/products/list` | Список товаров для выбора |
| `GET` | `/api/promotions/{id}/stats` | Статистика акции |
| `POST` | `/api/promotions/{id}/toggle-active` | Вкл/выкл акцию |

---

## Логика применения к заказу

```
POST /api/orders
    │
    ├── 1. Валидация промокода (если есть promo_code)
    ├── 2. Валидация совместимости промокода + акции
    │       └── если allow_promo_codes = false → ошибка 422
    ├── 3. Создание заказа
    ├── 4. Создание order_items
    ├── 5. Применение промокода (если выбран)
    └── 6. Применение акции (если promotion_id передан)
            ├── use_discount_instead = false → добавляется order_item (is_gift=true, price=0)
            ├── use_discount_instead = true → подарок не добавляется
            ├── создаётся запись в promotion_usages
            └── promotion.times_used++
```

---

## Чек-лист проверки

- [ ] Создать акцию с товаром-триггером и минимальной суммой
- [ ] Добавить товар-триггер в корзину на нужную сумму → акция должна появиться
- [ ] Убрать товар-триггер → акция исчезает
- [ ] `allow_promo_codes = false` → промокод не применяется, только подарок
- [ ] `allow_promo_codes = true` → можно выбрать подарок или промокод
- [ ] Оформить заказ с подарком → в `order_items` появляется позиция с `is_gift=true`, `price=0`
- [ ] Оформить заказ со скидкой → подарок не добавляется, промокод работает
- [ ] `promotion_usages` создаётся, `times_used` увеличивается
