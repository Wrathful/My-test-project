# Notification Microservice Architecture

## 1. High-Level Components and Their Responsibilities

### 1.1 API Gateway (Laravel Routes)
- Exposes RESTful endpoints for:
  - Triggering notification broadcasts (POST /notifications)
  - Querying notification status for a subscriber (GET /notifications/subscriber/{subscriberId})
- Validates incoming requests (channel, message text, recipient IDs)
- Assigns a unique request ID for idempotency checks
- Publishes notification jobs to the appropriate queue (based on priority)

### 1.2 Message Broker (RabbitMQ)
- Receives notification jobs from the API
- Routes jobs to appropriate queues based on priority:
  - High-priority queue for transactional notifications (e.g., OTP, urgent alerts)
  - Standard queue for marketing/batch notifications
- Provides persistence for message queues to survive broker restarts
- Supports message acknowledgments and dead-letter exchanges for failed messages

### 1.3 Worker Service (Laravel Queue Workers)
- Consumes notification jobs from the queues
- Processes each job by:
  - Checking idempotency in Redis (if already processed, skip)
  - Selecting the appropriate gateway provider (SMS/Email) based on channel
  - Attempting to send the notification via the provider adapter
  - Updating the notification status in the database
  - Handling retries on transient failures
  - Publishing delivery status updates (if needed for callbacks)

### 1.4 Database (PostgreSQL)
- Stores persistent records of notifications and their delivery status
- Tables:
  - `notifications`: metadata about each broadcast request (ID, channel, message, request_id, created_at)
  - `notification_recipients`: per-recipient status (notification_id, recipient_id, status, attempts, last_attempt_at, completed_at)
  - `delivery_attempts`: log of each attempt to send to a provider (for debugging and retry logic)

### 1.5 Cache Layer (Redis)
- Provides fast idempotency checks: stores recently processed request IDs to prevent duplicate processing
- Can be used for rate limiting if needed (e.g., limit requests per second per API key)
- Temporary storage for workflow state if sagas are implemented (not required for at-least-once)

### 1.6 Gateway Providers (Adapters)
- Abstract interfaces for SMS and Email providers
- Implementations are stubs/mocks for development and testing
- In production, would be replaced with actual provider integrations (Twilio, SendGrid, etc.)
- Each adapter returns a standardized result (success, temporary failure, permanent failure)

### 1.7 Monitoring and Logging
- Centralized logging (to stdout/stderr for Docker collection)
- Metrics collection (e.g., via Prometheus) for job processing times, failure rates, queue depths
- Health check endpoints for each service

## 2. Communication Patterns and Data Flow

### 2.1 Synchronous Flow (API Request)
1. Client sends POST /notifications with {channel, message, recipient_ids}
2. API validates request, generates a unique request_id (if not provided)
3. API checks Redis for recent request_id (idempotency) – if found, returns cached response
4. API creates a notification record in the database (status: queued)
5. For each recipient, creates a notification_recipient record (status: pending)
6. API publishes a job to RabbitMQ for each recipient (or a batch job) with the notification data
7. API returns 202 Accepted with the notification ID

### 2.2 Asynchronous Processing (Worker)
1. Worker consumes a job from the appropriate queue (high or normal priority)
2. Worker extracts notification data and recipient ID
3. Worker checks Redis for idempotency key (notification_id:recipient_id or similar) – if processed, skips
4. Worker attempts to send notification via the appropriate gateway adapter
5. On success:
   - Updates notification_recipient status to 'sent' (or 'delivered' if provider confirms immediately)
   - Records a successful delivery_attempt
   - Marks the recipient as done
6. On temporary failure (e.g., network timeout, provider 5xx):
   - Increments attempt count
   - Schedules a retry after exponential backoff (by re-publishing to a delay queue or using RabbitMQ TTL/DLX)
   - Updates delivery_attempt with failure info
7. On permanent failure (e.g., invalid number, blocked):
   - Marks notification_recipient as 'failed'
   - Records delivery_attempt as permanent failure
8. After processing, if idempotency key was not previously set, stores it in Redis with a TTL (e.g., 24 hours)

### 2.3 Status Query Flow
1. Client sends GET /notifications/subscriber/{subscriberId}
2. API queries the notification_recipients table for the given subscriber_id, joining with notifications to get message details
3. Returns list of notifications with their statuses (queued, sent, delivered, failed)

## 3. Technology Choices Justification

### 3.1 Language/Framework: Laravel (PHP)
- The existing project is built with Laravel, ensuring consistency and reuse of existing tooling (Eloquent ORM, Queue system, Validation, etc.)
- Laravel provides built-in support for Redis, database queues, and event broadcasting
- Rich ecosystem for testing (PHPUnit, Mockery) and API development
- Rapid development and mature community

### 3.2 Database: PostgreSQL
- Recommended in the task requirements
- Strong ACID compliance ensures data integrity for notification statuses
- Good performance for read-heavy workloads (status queries)
- Already available in the docker-compose setup

### 3.3 Message Broker: RabbitMQ
- Chosen over Kafka for simpler queue semantics (point-to-point) which fits the notification use case
- Built-in support for priority queues, TTL, and dead-letter exchanges
- Management UI for monitoring queues
- Already available in the docker-compose setup
- Suitable for at-least-once delivery with acknowledgments

### 3.4 Cache: Redis
- Extremely fast for idempotency checks (O(1) lookups)
- Supports TTL for automatic cleanup of old request IDs
- Can be used for rate limiting if needed
- Already available in the docker-compose setup

### 3.5 Containerization: Docker
- Ensures consistent environment across development and production
- All services (app, db, broker, cache) can be started with a single docker-compose up
- Easy to scale workers independently

## 4. Database Schema Design

### 4.1 notifications table
- id (UUID, primary key)
- channel (enum: sms, email)
- message_text (text)
- request_id (UUID, unique, for idempotency)
- created_at (timestamp)
- updated_at (timestamp)

### 4.2 notification_recipients table
- id (UUID, primary key)
- notification_id (foreign key to notifications.id)
- recipient_id (string, e.g., phone number or email address)
- status (enum: pending, sent, delivered, failed)
- attempts (integer, default 0)
- last_attempt_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)

### 4.3 delivery_attempts table (optional, for audit)
- id (UUID, primary key)
- notification_recipient_id (foreign key)
- attempt_number (integer)
- provider_response (text)
- created_at (timestamp)

## 5. API Design

### 5.1 Send Notification
- POST /api/v1/notifications
- Headers: Content-Type: application/json
- Body:
  {
    "channel": "smtp|sms",
    "message": "Text message",
    "recipients": ["+1234567890", "+0987654321"],
    "request_id": "optional-uuid-for-idempotency"
  }
- Success Response: 202 Accepted
  {
    "notification_id": "uuid",
    "status": "accepted",
    "message": "Notification queued for processing"
  }
- Error Responses:
  - 400 Bad Request (validation errors)
  - 409 Conflict (if request_id provided and already processed recently)

### 5.2 Get Notification Status for Subscriber
- GET /api/v1/notifications/subscriber/{subscriberId}
- Query Parameters: limit, offset (for pagination)
- Success Response: 200 OK
  [
    {
      "notification_id": "uuid",
      "channel": "sms",
      "message": "Your OTP is 1234",
      "recipient_id": "+1234567890",
      "status": "delivered",
      "created_at": "2024-01-01T10:00:00Z",
      "updated_at": "2024-01-01T10:00:05Z"
    }
  ]
- Error Responses:
  - 404 Not Found (if no notifications for subscriber)

## 6. Prioritization Mechanism

We will use RabbitMQ's priority queues feature. Queues can be configured with a max priority (e.g., 10). Messages published with a higher priority number are delivered before those with lower priority.

- Define two queues: 
  - `notifications.high` (max priority 10)
  - `notifications.normal` (max priority 5)
- When publishing a notification job, set the priority based on the notification type:
  - Transactional (OTP, alerts): priority = 10 -> goes to high queue
  - Marketing (newsletters, promotions): priority = 1 -> goes to normal queue
- Workers consume from the high queue first, then the normal queue (by consuming from both with appropriate prefetch or by having separate worker pools)

Alternative: Use a single queue with priority levels. Simpler to manage.

We'll implement a single queue named `notifications` with max priority 10. Then we set the message priority property when publishing.

## 7. Idempotency Mechanism

To prevent duplicate processing of the same request (due to retries from the caller), we use a combination of:

1. Request ID: The caller can provide a `request_id` in the API request. If not provided, we generate one (UUID v4).
2. Redis Store: We store a key for each request_id with a value of the notification ID (or just a flag) and a TTL (e.g., 24 hours).
3. Before processing a job, the worker checks if the request_id has been seen recently. If yes, it skips processing and returns the previous result (if available) or simply acknowledges the message without re-processing.

Additionally, we can make the notification creation step idempotent by having a unique constraint on the `request_id` column in the `notifications` table. This ensures that even if two requests with the same request_id hit the API simultaneously, only one notification record is created.

## 8. Retry Mechanism

### 8.1 Handling Transient Failures
When a gateway provider returns a temporary error (e.g., network timeout, 502 Bad Gateway), the worker should retry the operation.

We implement exponential backoff with a maximum number of attempts (e.g., 5 retries).

### 8.2 Implementation Options
Option 1: Use Laravel's built-in retry mechanism on the queue job (via `tries` and `retry_after` properties). However, this retries the entire job, which may include multiple recipients if we batch.

Option 2: Implement retry logic within the job handler for each recipient attempt, and only fail the job after all retries are exhausted for a recipient.

We choose option 2 for finer-grained control.

### 8.3 Dead Letter Queue
Messages that fail after all retries are sent to a dead letter queue (DLQ) for manual inspection. We can configure RabbitMQ to send messages to a DLQ after a certain number of failures.

In the DLQ, we can store the failed notification details and alert operators.

## 9. Delivery Guarantees

### 9.1 At-Least-Once Delivery
Our system ensures at-least-once delivery through:
- Persistent storage of jobs in RabbitMQ (disk-nodes or persistent messages)
- Acknowledgement mechanism: Workers only acknowledge a message after successfully processing it (or after deciding to skip due to idempotency)
- If a worker crashes before acknowledging, the message is redelivered to another consumer
- Database updates are done within a transaction (where applicable) to ensure consistency

### 9.2 Exactly-Once at Business Logic Level
While exactly-once delivery is impossible in distributed systems, we can achieve exactly-once processing (i.e., no duplicate notifications sent to the recipient) through:
- Idempotency checks using Redis (as described)
- Making the gateway adapter calls idempotent where possible (e.g., by passing a unique message ID that the provider can use to deduplicate)
- Database constraints (unique index on request_id) to prevent duplicate notification records

## 10. Testing Strategy

### 10.1 Unit Tests
- Test individual classes (e.g., NotificationService, GatewayAdapter) with mocks
- Use PHPUnit and Mockery

### 10.2 Integration Tests
- Test the full flow from API request to database update
- Use an in-memory SQLite database for speed
- Mock the gateway providers to simulate success, temporary failure, and permanent failure
- Test idempotency by sending the same request twice and verifying only one notification is sent
- Test prioritization by sending high and normal priority messages and verifying the order of processing
- Test retry mechanisms by simulating temporary failures

### 10.3 End-to-End Tests (Optional)
- Use Laravel Dusk or external tools to test against a real RabbitMQ and PostgreSQL (via Docker Compose in CI)
- Validate that notifications are actually sent to mock providers (we can use tools like MailHog for email and a fake SMS gateway)

## 11. Deployment Architecture

We will extend the existing docker-compose.yml to include the necessary services for the notification microservice. Since we are reusing the existing Laravel application (the notification module is part of the same codebase), we do not need a separate application container. However, we need to ensure that queue workers are running for the notification queues.

We can add a worker service to the docker-compose that runs the queue worker specifically for the notification queues.

### 11.1 Services
- `app`: The existing PHP service (already in docker-compose) that serves HTTP requests (including notification API endpoints)
- `notification-worker`: A new service that runs `php artisan queue:work --queue=notifications.high,notifications.default --sleep=3 --tries=3`
- `postgres`: PostgreSQL database (we'll use the existing postgres service or add a dedicated one if needed)
- `redis`: Redis cache (existing)
- `rabbitmq`: RabbitMQ broker (existing)

### 11.2 Networking
All services will be on the same default network to allow communication.

### 11.3 Volumes
- Persistent volumes for PostgreSQL and RabbitMQ data
- The application code is mounted from the host (./app:/var/www/html) so changes are reflected immediately

### 11.4 Environment Variables
- Database connection variables (DB_HOST, DB_PORT, etc.)
- Redis connection variables
- RabbitMQ connection variables
- Queue connection variables (we'll configure a queue connection named 'rabbitmq' in Laravel's config/queue.php)

### 11.5 Docker Compose Changes
We will add a new service for the worker and update the existing app service to ensure it has the necessary environment variables for connecting to the services.

Note: The existing docker-compose.yml already has services for postgres, redis, rabbitmq, etc. We may need to adjust the app service to use postgres instead of mariadb (since we chose PostgreSQL). Currently, the app service uses both mariadb (db) and postgres (postgre). We'll configure Laravel to use the postgres connection.

We'll create a separate docker-compose.override.yml or modify the existing one to suit our needs. For simplicity, we'll create a new docker-compose.yml that extends the existing one, but given the instructions, we should modify the existing docker-compose.yml to include the worker and adjust the app service to use PostgreSQL.

However, note that the existing project uses mariadb as the default db service. We have both mariadb and postgres available. We'll configure Laravel to use the postgres service.

Let's check the existing .env file to see which database is configured.

But since we are in architect mode, we cannot modify .env or code yet. We are only designing.

We'll note in the architecture that the deployment will use the existing services and add a worker.

## 12. Next Steps

With this architecture document, we can proceed to implement the notification module. The next steps would be:

1. Create database migrations for the notifications tables
2. Create the API routes and controllers
3. Create the notification job classes and workers
4. Implement the gateway adapters (with mocks)
5. Configure Redis for idempotency
6. Write integration tests
7. Update docker-compose to include the worker service
8. Configure Laravel queue connections and drivers

We will now mark the first task as complete and move on to the next.
