# Nimpix

API для управления рабочими процессами (workflows) и задачами (tasks).

## Стек

- **Laravel 13**, **PHP 8.3+**, **Sanctum** (токен-аутентификация)
- **PostgreSQL 16** (Docker) / **SQLite** (тесты)
- **Scramble** — OpenAPI-документация (`/docs/api`)
- **Pint** — PHP code style

## Запуск

```bash
docker compose up -d                        # старт
docker exec nimpix-api php artisan migrate  # миграции
docker exec nimpix-api php artisan test     # тесты
```

## API

| Метод | Эндпоинт | Доступ |
|--------|----------|--------|
| POST | `/api/register` | Публичный |
| POST | `/api/login` | Публичный |
| POST | `/api/forgot-password` | Публичный |
| POST | `/api/reset-password` | Публичный |
| GET | `/api/email/verify/{id}/{hash}` | Signed URL |
| GET | `/api/me` | auth:sanctum |
| POST | `/api/logout` | auth:sanctum |
| CRUD | `/api/workflows`, `/api/tasks` | auth:sanctum |
| PUT | `/api/tasks/reorder` | auth:sanctum |
| GET | `/up` | Health-check (БД, кэш) |

Документация API: `http://localhost:8000/docs/api`

## CI/CD

- **GitHub Actions**: lint (Pint) + тесты (PHP 8.3, 8.4, 8.5)
- **GitLab CI**: lint + тесты (PHP 8.4, PostgreSQL)

## Лицензия

MIT
