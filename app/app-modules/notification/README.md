# Notification Service Module

Микросервис уведомлений для массовой рассылки SMS и Email сообщений.

## Возможности

- Массовая рассылка уведомлений (SMS/Email)
- Приоритезация трафика (транзакционные vs маркетинговые)
- Дедупликация запросов (идемпотентность)
- Автоматические повторные попытки при ошибках
- Отслеживание статуса доставки

## Требования

- PHP 8.3+
- Laravel 13.x
- MySQL/PostgreSQL
- Redis (для очередей и кэша)

## Установка

1. Убедитесь, что модуль подключен в `app/composer.json`:
```json
"require": {
    "modules/notification": "*"
}
```

2. Выполните миграции:
```bash
docker-compose exec php php artisan migrate
```

## API

### POST /api/v1/notifications

Создание массовой рассылки уведомлений.

**Request Body:**
```json
{
    "channel": "sms|email",
    "message": "Текст сообщения",
    "recipients": ["+1234567890", "user@example.com"],
    "request_id": "optional-uuid-for-idempotency",
    "is_transactional": true
}
```

**Parameters:**
- `channel` (required) - канал отправки: `sms` или `email`
- `message` (required) - текст сообщения
- `recipients` (required) - массив получателей (телефонные номера или email)
- `request_id` (optional) - UUID для идемпотентности
- `is_transactional` (optional) - `true` для транзакционных уведомлений (высокий приоритет)

**Response (202 Accepted):**
```json
{
    "notification_id": 1,
    "status": "accepted",
    "message": "Notification queued for processing"
}
```

**Response (200 OK - дубликат):**
```json
{
    "notification_id": 1,
    "status": "duplicate",
    "message": "Notification with this request_id was already processed."
}
```

**Response (422 - ошибка валидации):**
```json
{
    "message": "The selected channel is invalid.",
    "errors": {
        "channel": ["The selected channel is invalid."]
    }
}
```

### GET /api/v1/notifications/subscriber/{subscriberId}

Получение истории уведомлений для подписчика.

**Query Parameters:**
- `limit` (default: 50) - количество записей
- `offset` (default: 0) - смещение

**Response (200 OK):**
```json
[
    {
        "notification_id": 1,
        "channel": "sms",
        "message": "Your OTP is 1234",
        "recipient_id": "+1234567890",
        "status": "sent",
        "created_at": "2024-01-01T10:00:00.000000Z",
        "updated_at": "2024-01-01T10:00:05.000000Z"
    }
]
```

## Приоритезация

Система поддерживает два уровня приоритета:

- **Транзакционные уведомления** (`is_transactional: true`) - отправляются в очередь `notifications` с высоким приоритетом
- **Маркетинговые уведомления** (`is_transactional: false` или не указано) - отправляются в очередь `notifications`

## Запуск в Docker

```bash
docker-compose up -d
```

Сервисы:
- `php` - веб-приложение и воркер
- `db` - MySQL
- `redis` - Redis (для очередей и кэша)

## Запуск воркера

Для обработки очередей уведомлений:

```bash
# Обработка всех очередей
docker-compose exec php php artisan queue:work --queue=notifications --tries=3
```

## Тестирование

```bash
# Все тесты
docker-compose exec php php artisan test

# Тесты модуля уведомлений
docker-compose exec php php artisan test --filter=NotificationTest
```

## Статусы уведомлений

- `pending` - в очереди, ожидает отправки
- `sent` - передано шлюзу/провайдеру
- `failed` - отброшено (ошибка доставки)

## Архитектура

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   API Request   │────▶│  Notification    │────▶│   Redis Queue   │
│  (POST /api/v1) │     │   Controller     │     │  (high/low)     │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                                           │
                                                           ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Gateway Mock   │◀────│ SendNotification │◀────│   Queue Worker  │
│ (SMS/Email)     │     │      Job         │     │                 │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                                           │
                                                           ▼
                                                ┌────────────────────┐
                                                │  Database (MySQL)  │
                                                │  - notifications   │
                                                │  - notification_   │
                                                │    recipients      │
                                                │  - delivery_       │
                                                │    attempts        │
                                                └────────────────────┘
```

## Интеграция с реальными провайдерами

Для интеграции с реальными SMS/Email провайдерами замените классы-заглушки:

- `Modules\Notification\Services\Gateway\SmsGateway` - SMS провайдер (Twilio, AWS SNS)
- `Modules\Notification\Services\Gateway\EmailGateway` - Email провайдер (SendGrid, AWS SES)
