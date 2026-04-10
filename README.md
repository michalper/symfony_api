# Game Love Service

REST API that keeps track of the games that players love.

Based on the [comeon-gamelove-assignment](https://github.com/comeon-group/comeon-gamelove-assignment), implemented in **PHP 8.4+ / Symfony 8**.

## Tech Stack

- PHP 8.4+
- Symfony 8
- Doctrine ORM
- SQLite (file-based embedded database)
- PHPUnit 13

## Architecture

- **Controllers** — thin HTTP layer: parse requests, validate input, delegate to services, format responses
- **Services** (`PlayerService`, `GameService`, `GameLoveService`) — business logic and persistence orchestration
- **DTOs** (`CreatePlayerInput`, `CreateGameInput`, `LoveGameInput`) — validated request objects using Symfony Validator (`NotBlank`, `Length`, `Positive`)
- **Repositories** — data access behind interfaces for testability
- **Exceptions** — domain exceptions mapped to HTTP status codes in controllers

## API Endpoints

| Method | Path                                     | Description                        |
|--------|------------------------------------------|------------------------------------|
| POST   | `/api/games`                             | Create a game                      |
| GET    | `/api/games?page=1&limit=20`             | List games (paginated)             |
| GET    | `/api/games/top?limit=10`                | Top loved games (limit adjustable) |
| POST   | `/api/players`                           | Create a player                    |
| GET    | `/api/players?page=1&limit=20`           | List players (paginated)           |
| POST   | `/api/players/{playerId}/loves`          | Love a game (`{"gameId": 1}`)      |
| DELETE | `/api/players/{playerId}/loves/{gameId}` | Unlove a game                      |
| GET    | `/api/players/{playerId}/loves`          | List games loved by player         |

## Setup

```bash
cp .env.dist .env
composer install
php bin/console doctrine:schema:create
```

## Run

```bash
php -S localhost:8000 -t public
```

## Tests

```bash
./vendor/bin/phpunit
```

55 tests (38 unit + 17 functional) covering all endpoints, validation, pagination, and edge cases.

## Example Usage

```bash
# Create a game
curl -X POST http://localhost:8000/api/games \
  -H 'Content-Type: application/json' \
  -d '{"title": "Counter-Strike 2"}'

# Create a player
curl -X POST http://localhost:8000/api/players \
  -H 'Content-Type: application/json' \
  -d '{"name": "Alice"}'

# Love a game
curl -X POST http://localhost:8000/api/players/1/loves \
  -H 'Content-Type: application/json' \
  -d '{"gameId": 1}'

# Get top 5 loved games
curl http://localhost:8000/api/games/top?limit=5

# List games (page 2, 5 per page)
curl http://localhost:8000/api/games?page=2&limit=5

# Unlove a game
curl -X DELETE http://localhost:8000/api/players/1/loves/1
```
