# Notification Service

Микросервис уведомлений для массовой рассылки SMS и Email сообщений.

## Возможности

- Массовая рассылка уведомлений (SMS/Email)
- Приоритезация трафика (транзакционные vs маркетинговые)
- Дедупликация запросов (идемпотентность)
- Автоматические повторные попытки при ошибках
- Отслеживание статуса доставки

## Требования

- Docker и Docker Compose
- PHP 8.3+
- Laravel 13.x
- MySQL
- Redis

## Быстрый старт

```bash
# Клонирование репозитория
git clone <repository-url>
cd my-test-project

# Запуск всех сервисов
docker-compose up -d

# Выполнение миграций
docker-compose exec php php artisan migrate

# Запуск воркера для обработки очередей
docker-compose exec php php artisan queue:work --queue=notifications-high,notifications-low --tries=3
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
- `recipients` (required) - массив получателей
- `request_id` (optional) - UUID для идемпотентности
- `is_transactional` (optional) - `true` для транзакционных уведомлений

**Response (202 Accepted):**
```json
{
    "notification_id": 1,
    "status": "accepted",
    "message": "Notification queued for processing"
}
```

### GET /api/v1/notifications/subscriber/{subscriberId}

Получение истории уведомлений для подписчика.

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

- **Транзакционные уведомления** (`is_transactional: true`) - очередь `notifications-high`
- **Маркетинговые уведомления** - очередь `notifications-low`

## Тестирование

```bash
docker-compose exec php php artisan test
```

## Статусы уведомлений

- `pending` - в очереди, ожидает отправки
- `sent` - передано шлюзу/провайдеру
- `failed` - отброшено (ошибка доставки)

## Архитектура

См. [app/app-modules/notification/README.md](app/app-modules/notification/README.md) для детальной документации.
