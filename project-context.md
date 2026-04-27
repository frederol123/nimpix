# Nimpix — Project Context

## Стек

- **Laravel** 13.6, **PHP** ^8.3, **Sanctum** 4.3 (token API auth)
- **PostgreSQL** 16 (docker), **SQLite** in-memory (testing)
- **Vite** 8, **Tailwind CSS** 4, **PHPUnit** 12.5
- **Docker**: `nimpix-api` (php), `nimpix-db` (postgres:16)

## Запуск

```
docker compose up -d                        # старт контейнеров
docker exec nimpix-api php artisan test     # тесты
docker exec nimpix-api php artisan migrate  # миграции
```

## Модели

### User
- Поля: `name`, `email`, `password` (hashed), `email_verified_at`, `remember_token`
- Fillable: `name`, `email`, `password`
- Traits: HasApiTokens, HasFactory, MustVerifyEmail, Notifiable
- Implements: `MustVerifyEmail` (верификация email)
- Связи: `hasMany(Workflow::class)`
- Переопределён: `sendPasswordResetNotification($token)` → кастомная `ResetPasswordNotification`

### Workflow
- Поля: `user_id` (FK users, cascadeOnDelete), `name` (str), `description` (text, nullable), `status` (str, default `draft`), `deleted_at` (soft delete)
- Fillable: `user_id`, `name`, `description`, `status`
- Связи: `belongsTo(User::class)`, `hasMany(Task::class)`
- Traits: HasFactory, SoftDeletes

### Task
- Поля: `workflow_id` (FK cascadeOnDelete), `name` (str), `description` (text, nullable), `status` (str, default `pending`), `position` (int, default 0), `due_at` (datetime, nullable), `deleted_at` (soft delete)
- Fillable: `workflow_id`, `name`, `description`, `status`, `position`, `due_at`
- Casts: `due_at` => datetime
- Связи: `belongsTo(Workflow::class)`
- Traits: HasFactory, SoftDeletes

## Политики (Policies)

### WorkflowPolicy
- `viewAny` → true (все аутентифицированные)
- `view`, `update`, `delete` → только если `$user->id === $workflow->user_id`
- `create` → true (все аутентифицированные)

### TaskPolicy
- `viewAny` → true (все аутентифицированные)
- `view`, `update`, `delete` → только если `$user->id === $task->workflow->user_id`
- `create` → true (все аутентифицированные)

Привязка: FormRequests `authorize()` + `$this->authorize()` в show/destroy контроллеров.
При провале возвращает 403 через `AccessDeniedHttpException` → обрабатывается в `bootstrap/app.php`.

## API Resources

Все контроллеры используют Resources вместо сырых моделей:
- **UserResource** — `id, name, email, email_verified_at, created_at, updated_at` (без password, remember_token)
- **WorkflowResource** — `id, name, description, status, user` (whenLoaded), `tasks` (whenLoaded), timestamps
- **TaskResource** — `id, workflow_id, name, description, status, position, due_at, workflow` (whenLoaded), timestamps

Single Resource (show/update/store) оборачивается в `data`, collection (index) — `data` + `links` + `meta` (pagination).

## API Эндпоинты

### Публичные (throttle 10/мин)
| Метод | URI | Действие |
|--------|-----|----------|
| POST | `/api/register` | Регистрация → token + UserResource, отправляет verify-email |
| POST | `/api/login` | Вход (email, password) → token + UserResource |
| POST | `/api/forgot-password` | Отправляет reset-токен на email (ResetPasswordNotification) |
| POST | `/api/reset-password` | Сброс пароля (email, token, password, password_confirmation) |
| GET | `/api/email/verify/{id}/{hash}` | Верификация email (signed middleware, без auth) — имя роута: `verification.verify` |

### Защищённые (auth:sanctum, throttle 60/мин)
| Метод | URI | Действие |
|--------|-----|----------|
| GET | `/api/me` | Текущий пользователь (UserResource) |
| POST | `/api/logout` | Выход (удаление токена) |
| POST | `/api/email/verification-notification` | Повторная отправка verify-email (throttle 6/мин) — имя: `verification.send` |
| GET/POST | `/api/workflows` | Список (paginate) / Создание (user_id из auth) |
| GET/PUT/DELETE | `/api/workflows/{id}` | Просмотр / Обновление / Удаление (soft delete) |
| PUT | `/api/tasks/reorder` | Массовое изменение position (tasks: [{id, position}]) — перед apiResource |
| GET/POST | `/api/tasks` | Список (paginate + фильтры + сортировка) / Создание |
| GET/PUT/DELETE | `/api/tasks/{id}` | Просмотр / Обновление / Удаление (soft delete) |

## Валидация

- **Регистрация**: name (req, str, max:255), email (req, email, unique:users), password (req, min:8, confirmed)
- **Логин**: email (req, email), password (req, str)
- **Forgot password**: email (req, email)
- **Reset password**: email (req, email), token (req), password (req, confirmed, Rules\Password::defaults)
- **Workflow**: name (req, str, max:255), description (nullable, str), status (req, str, max:50)
- **Task**: workflow_id (req, int, exists:workflows где user_id = auth()->id()), name (req, str, max:255), description (nullable, str), status (req, str, max:50), position (nullable, int, min:0), due_at (nullable, date)
- **Task reorder**: tasks (req, array), tasks.*.id (req, int, distinct), tasks.*.position (req, int, min:0)

## Фильтрация и сортировка задач

`GET /api/tasks` принимает query-параметры:
- `status` — фильтр по статусу задачи
- `workflow_id` — фильтр по workflow
- `due_at_from` — due_at >= дата
- `due_at_to` — due_at <= дата
- `sort` — поле сортировки (created_at, due_at, name, status, position), default: created_at
- `direction` — asc/desc, default: desc

## Email / Нотификации

- **Верификация**: стандартная `VerifyEmail` от Laravel, URL = signed route `verification.verify` на `/api/email/verify/{id}/{hash}`
- **Сброс пароля**: кастомная `ResetPasswordNotification` — токен в теле письма, инструкция к `/api/reset-password`
- `config/auth.php`: `verification.expire` = 60 (минут), `passwords.users.expire` = 60, `throttle` = 60

## Обработка ошибок (bootstrap/app.php)

API JSON-ответы с полем `success`:
| Exception | Статус | Сообщение |
|-----------|--------|-----------|
| ValidationException | 422 | Validation failed. + errors |
| AuthenticationException | 401 | Unauthenticated. |
| AccessDeniedHttpException | 403 | Forbidden. |
| NotFoundHttpException | 404 | Resource not found. |
| Throwable | 500 | Internal server error. |

Контроллер `Controller` использует `AuthorizesRequests` trait для `$this->authorize()`.

## Миграции

| Файл | Назначение |
|------|-----------|
| `0001_01_01_000000` | users, password_reset_tokens, sessions |
| `0001_01_01_000001` | cache, cache_locks |
| `0001_01_01_000002` | jobs, job_batches, failed_jobs |
| `2026_04_24_163415` | workflows (id, name, description, status, timestamps) |
| `2026_04_24_163416` | tasks (id, workflow_id FK cascadeOnDelete, name, description, status, position, due_at, timestamps) |
| `2026_04_24_165834` | personal_access_tokens (Sanctum) |
| `2026_04_27_100000` | ✅ Этап 1: adds user_id FK to workflows (cascadeOnDelete) |
| `2026_04_27_100001` | ✅ Этап 1: adds softDeletes (deleted_at) to workflows and tasks |

## Ключевые файлы

| Файл | Назначение |
|------|-----------|
| `routes/api.php` | Все API-роуты (публичные + auth:sanctum) |
| `app/Models/*.php` | User, Workflow, Task |
| `app/Policies/*.php` | WorkflowPolicy, TaskPolicy |
| `app/Http/Resources/*.php` | UserResource, WorkflowResource, TaskResource |
| `app/Http/Controllers/Api/AuthController.php` | register, login, me, logout |
| `app/Http/Controllers/Api/WorkflowController.php` | CRUD workflows |
| `app/Http/Controllers/Api/TaskController.php` | CRUD tasks + reorder + filtering |
| `app/Http/Controllers/Api/VerificationController.php` | verify (signed), send (re-send verify email) |
| `app/Http/Controllers/Api/PasswordResetController.php` | forgot (send token), reset (change password) |
| `app/Http/Controllers/Controller.php` | Базовый контроллер с AuthorizesRequests |
| `app/Http/Requests/*.php` | StoreWorkflow, UpdateWorkflow, StoreTask, UpdateTask — все с policy authorize() |
| `app/Notifications/ResetPasswordNotification.php` | Кастомное письмо сброса пароля с токеном |
| `bootstrap/app.php` | Exception handlers + middleware |
| `config/auth.php` | guards, providers, passwords, verification |
| `database/factories/*.php` | UserFactory, WorkflowFactory (user_id), TaskFactory |
| `database/seeders/DatabaseSeeder.php` | 1 тестовый пользователь |
| `database/migrations/*.php` | 8 миграций |
| `docker-compose.yml` | db (postgres:16) + api (laravel-php) |
| `phpunit.xml` | SQLite :memory:, RefreshDatabase |
| `plan.md` | План развития проекта |

## Тесты

- `tests/Feature/AuthTest.php` — 12 тестов: register, login, me, logout + валидация + password absence в UserResource
- `tests/Feature/WorkflowTest.php` — 12 тестов: CRUD + 404 + 403 (чужой workflow) + unauth + cascade forceDelete
- `tests/Feature/TaskTest.php` — 13 тестов: CRUD + 404 + 403 (чужой task) + unauth + validation (включая workflow ownership)
- `tests/Feature/ExampleTest.php` — 1 тест: /
- Запуск: `docker exec nimpix-api php artisan test`
- Всего: 38 тестов, 98 assertions
