# WSOP Fantasy module

Модуль `wsop-fantasy` реализует конкурс GipsyTeam WSOP Fantasy: пользователи собирают одну команду из 7 игроков WSOP, по одному из каждой группы, с общей стоимостью не выше `180`, назначают капитана и попадают в общий лидерборд по POY-очкам.

## Реализованные возможности

- Структура БД для пользователей сайта, групп игроков, игроков, команд, состава команд и истории POY-очков.
- Ограничение: один черновик команды на пользователя и одна отправленная команда на пользователя.
- Валидация команды:
  - выбрано ровно 7 игроков;
  - по одному игроку из каждой группы;
  - игрок принадлежит выбранной группе;
  - суммарная стоимость команды не превышает `180`;
  - капитан входит в выбранный состав.
- Расчет командного POY:
  - обычный игрок дает свой текущий POY-счет;
  - капитан дает POY-счет, умноженный на `1.5`;
  - результат округляется до целого числа.
- Страница формы команды и страница лидерборда.
- Artisan-команда импорта POY-очков.
- Cron-задача Laravel Scheduler для ежечасного импорта POY-очков.

## Структура модуля

Основные файлы:

- `composer.json` — PSR-4 namespace `Modules\WsopFantasy\` и Laravel service provider.
- `database/migrations/2026_06_10_000000_create_gipsyteam_users_table.php` — таблица `gipsyteam_user`.
- `database/migrations/2026_06_10_000001_create_wsop_groups_table.php` — группы игроков.
- `database/migrations/2026_06_10_000002_create_wsop_players_table.php` — игроки WSOP с группой и стоимостью.
- `database/migrations/2026_06_10_000003_create_wsop_teams_table.php` — команды пользователей.
- `database/migrations/2026_06_10_000004_create_wsop_team_players_table.php` — состав команды и капитан.
- `database/migrations/2026_06_10_000005_create_wsop_poy_scores_table.php` — история POY-очков игроков.
- `database/seeders/WsopFantasySeeder.php` — тестовые группы, игроки, пользователи и команды.
- `src/Console/Commands/ImportPoyCommand.php` — команда `wsop-fantasy:import-poy`.
- `src/Services/TeamValidationService.php` — валидация команды и расчет очков.
- `src/Services/TeamCreationService.php` — сохранение черновика и отправка команды.
- `src/Services/LeaderboardService.php` — данные для лидерборда.
- `src/Services/PoyImportService.php` — импорт POY-очков.
- `src/Providers/WsopFantasyServiceProvider.php` — регистрация конфига и cron-задачи.
- `routes/wsop-fantasy-routes.php` — HTTP-маршруты модуля.
- `resources/views/team/form.blade.php` — форма создания команды.
- `resources/views/leaderboard/index.blade.php` — страница лидерборда.

## Маршруты

Проверка маршрутов:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan route:list --path=wsop-2026-fantasy'
```

Доступные маршруты:

- `GET /wsop-2026-fantasy` — форма создания команды.
- `POST /wsop-2026-fantasy/submit` — отправка команды.
- `GET /wsop-2026-fantasy/leaderboard` — лидерборд.
- `GET /__wsop-fantasy-login/{login}` — простенькая авторизация для тестирования.

## Artisan-команды

### `wsop-fantasy:import-poy`

Команда импортирует POY-очки для всех игроков из `wsop_players` и создает новые записи в `wsop_poy_scores`, сохраняя историю изменений.

Проверка, что команда зарегистрирована:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan list | grep wsop-fantasy'
```

Помощь по команде:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan wsop-fantasy:import-poy --help'
```

Запуск импорта:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan wsop-fantasy:import-poy'
```

Важно: текущая реализация `PoyImportService` использует случайные целочисленные значения POY для демонстрации, как разрешено условием задания. В реальном проекте метод `fetchPlayerScore()` нужно заменить на HTTP/API-запрос к источнику и парсинг колонки `Score`.

## Cron / Laravel Scheduler

В `WsopFantasyServiceProvider` добавлена cron-задача. В задании указано, что POY-очки импортируются раз в час, поэтому команда запускается ежечасно:

```php
$this->app->booted(function () {
    $schedule = $this->app->make(Schedule::class);

    $schedule->command(ImportPoyCommand::class)
        ->hourly()
        ->runInBackground();
});
```

На сервере Laravel Scheduler должен запускаться cron каждую минуту:

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1
```

В Docker-контейнере проверку расписания можно выполнить командой:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan schedule:list | grep wsop-fantasy'
```

Ожидаемый результат должен содержать команду `wsop-fantasy:import-poy`, например:

```text
0 * * * *  php artisan wsop-fantasy:import-poy
```

## Локальная установка

Команды выполняются из корня проекта `/var/www/Docker/my-test-project`.

1. Установить зависимости PHP:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && composer install --no-interaction --prefer-dist'
```

2. При необходимости обновить autoload после добавления файлов модуля:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && composer dump-autoload --no-interaction'
```

3. Очистить кеш конфигурации и пакетов:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan config:clear && php artisan package:discover --ansi && php artisan modules:clear'
```

4. Запустить миграции:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan migrate --force'
```

5. Запустить seeder модуля для тестовых групп, игроков, пользователей и команд:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan db:seed --class="Modules\WsopFantasy\Database\Seeders\WsopFantasySeeder"'
```

Если нужно очистить только данные конкурса перед повторным запуском seeder, используйте откат миграций модуля или вручную очистите таблицы `wsop_poy_scores`, `wsop_team_players`, `wsop_teams`, `wsop_players`, `wsop_groups` и `gipsyteam_user`.

6. Запустить сборку фронтенда, если требуется:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && npm install --ignore-scripts && npm run build'
```

## Откат миграций

Для локальной проверки миграций:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan migrate:rollback --force'
```

После отката можно заново выполнить:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan migrate --force'
```

## Тестовый пользователь и локальная авторизация

Сиддер `WsopFantasySeeder` создает тестовые данные:

- **7 групп игроков** (по 27 игроков в каждой)
- **189 игроков** (27 игроков × 7 групп) со случайной стоимостью от 1 до 100
- **10 пользователей сайта** в таблице `gipsyteam_user`:
  - `player1`
  - `player2`
  - `player3`
  - `player4`
  - `player5`
  - `player6`
  - `player7`
  - `player8`
  - `player9`
  - `player10`
- **10 команд** (по одной на каждого пользователя) с автоматически сгенерированными именами в формате `User {id}'s Team`
- **POY очки** для игроков команд (случайные значения от 0 до 1000)

POY очки генерируются случайными значениями от 0 до 1000 для демонстрации, как разрешено условием задания.

Команда импорта `wsop-fantasy:import-poy` создает новые записи в `wsop_poy_scores` для каждого игрока, сохраняя историю изменений очков.

Модуль использует middleware [`GipsyteamAuthMiddleware`](app/app-modules/wsop-fantasy/src/Http/Middleware/GipsyteamAuthMiddleware.php:22). Он считает пользователя авторизованным, если:

1. `$request->user()` уже возвращает экземпляр `GipsyteamUser`; так будет после подключения реальной авторизации сайта GipsyTeam.
2. В сессии есть ключ `gipsyteam_user_id` с ID пользователя из таблицы `gipsyteam_user`.

Если пользователь не авторизован, middleware перенаправляет на маршрут `wsop-fantasy.debug.login` с параметром `test` (только в dev-режиме) или на страницу входа сайта.

В текущем skeleton-приложении нет отдельной страницы входа. Для локальной проверки добавлен dev-only маршрут, который доступен только при `APP_ENV=local` и `APP_DEBUG=true`:

```text
GET /__wsop-fantasy-login/{login}
```

Маршрут принимает логин из URL. Если пользователь с таким логином уже есть, он авторизует его. Если пользователя нет, он создает новую запись в `gipsyteam_user` с этим логином и текущим временем `insert_datetime`, затем авторизует созданного пользователя.

Например, откройте в браузере:

```text
http://my-project.test/__wsop-fantasy-login/test-user
```

Маршрут сохранит ID найденного или созданного пользователя в `gipsyteam_user_id` и перенаправит на форму команды. После этого можно открыть:

```text
http://my-project.test/wsop-2026-fantasy
```

Для проверки авторизации на существующем пользователе из seeder-а можно открыть, например:

```text
http://my-project.test/__wsop-fantasy-login/player1
```

Если вы не используете dev-only маршрут, авторизацию нужно подключить в основной системе сайта так, чтобы `$request->user()` возвращал `Modules\WsopFantasy\Models\GipsyteamUser` или чтобы в сессии выставлялся `gipsyteam_user_id`.

Проверка пользователей в БД:

```bash
docker exec my-project-php-1 sh -c "cd /var/www/html && php artisan tinker --execute='dump(\\Modules\\WsopFantasy\\Models\\GipsyteamUser::pluck(\"login\", \"id\")->all());'"
```

## Проверка функциональности

### Composer

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && composer validate --strict'
```

### Миграции

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan migrate --force'
```

### Тесты модуля

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan test --testsuite=AppModules'
```

### Команда импорта POY

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan list | grep wsop-fantasy'
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan wsop-fantasy:import-poy --help'
```

Не запускайте `php artisan wsop-fantasy:import-poy` на реальной базе без понимания side effects: команда создает новые записи в `wsop_poy_scores` для каждого игрока.

### Scheduler

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan schedule:list | grep wsop-fantasy'
```

Ожидаемый результат:

```text
0 * * * *  php artisan wsop-fantasy:import-poy
```

## Проверка на реальном сайте

1. Убедиться, что модуль подключен в Composer и provider обнаружен:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && composer dump-autoload --no-interaction'
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan package:discover --ansi'
```

2. Применить миграции на staging/prod после бэкапа:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan migrate --force'
```

3. Заполнить справочники игроков и групп из актуального Google Sheets или другого источника.
4. Проверить маршруты формы и лидерборда:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan route:list --path=wsop-2026-fantasy'
```

5. Проверить регистрацию команды:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan list | grep wsop-fantasy'
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan wsop-fantasy:import-poy --help'
```

6. На staging сначала запустить импорт и проверить, что в `wsop_poy_scores` появились свежие записи:

```bash
docker exec my-project-php-1 sh -c "cd /var/www/html && php artisan tinker --execute='dump(\\Modules\\WsopFantasy\\Models\\PoyScore::latest(\"id\")->count());'"
```

7. Настроить cron для Laravel Scheduler:

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/laravel-schedule.log 2>&1
```

8. Проверить scheduler:

```bash
docker exec my-project-php-1 sh -c 'cd /var/www/html && php artisan schedule:list | grep wsop-fantasy'
```
