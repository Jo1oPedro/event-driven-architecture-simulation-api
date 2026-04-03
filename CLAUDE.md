# CLAUDE.md

## Project Overview

Symfony 8.0 skeleton app running on FrankenPHP + Caddy via Docker. Includes Mercure (real-time), Vulcain (preloading), auto HTTPS, HTTP/3, and XDebug.

## Tech Stack

- **PHP:** 8.5+ / **Symfony:** 8.0
- **Server:** FrankenPHP with Caddy (worker mode)
- **Testing:** PHPUnit 13
- **Package Manager:** Composer 2

## Project Structure

```
src/              # Application code (PSR-4: App\)
  Controller/     # Controllers (attribute-based routing)
  Kernel.php      # MicroKernelTrait
config/           # Symfony config (services, routes, packages)
tests/            # PHPUnit tests (PSR-4: App\Tests\)
public/           # Web root (index.php)
frankenphp/       # Caddy config, PHP ini files, entrypoint script
bin/              # console, phpunit
docs/             # Documentation (setup, deployment, xdebug, etc.)
```

## Common Commands

```bash
# Start the project
docker compose build --pull --no-cache
docker compose up --wait

# Symfony console
docker compose exec php bin/console <command>

# Run tests
docker compose exec php bin/phpunit

# Install dependencies
docker compose exec php composer install

# Create controller/entity
docker compose exec php bin/console make:controller
docker compose exec php bin/console make:entity
```

## Architecture Decisions

- **Attribute-based routing** on controllers (no route YAML per controller)
- **Autowiring + autoconfiguration** enabled for all services in `src/`
- **Micro-kernel** approach — minimal boilerplate
- **Docker entrypoint** (`frankenphp/docker-entrypoint.sh`) handles: composer install, DB wait, migrations

## Code Standards

### Controllers

- Controllers devem ser **magros** — apenas recebem request, chamam service e retornam response
- Nunca colocar lógica de negócio no controller
- Nunca fazer Doctrine queries no controller — isso é responsabilidade do Repository
- Usar injeção de dependência via constructor ou method injection

### Services

- Toda lógica de negócio deve ficar em **Services** (`src/Service/`)
- Um service por responsabilidade (Single Responsibility Principle)
- Services recebem dependências via constructor injection (autowiring)
- Nomear de forma clara: `OrderService`, `PaymentProcessor`, `InvoiceGenerator`

### DTOs

- Sempre usar **DTOs** para request e response de API — nunca expor entidades diretamente
- Request DTOs com Symfony Validation Constraints (`#[Assert\NotBlank]`, etc.)
- Response DTOs para controlar exatamente o que é retornado ao cliente
- Organizar em `src/DTO/Request/` e `src/DTO/Response/`

### Entities & Repositories

- Entidades são apenas representação de dados — sem lógica de negócio complexa
- Queries customizadas ficam no **Repository** (`src/Repository/`)
- Usar QueryBuilder ou DQL no repository, nunca raw SQL (exceto casos de performance crítica)
- Validação de entidade via Symfony Constraints nos atributos

### SOLID

- **S** — Single Responsibility: cada classe tem uma única razão para mudar
- **O** — Open/Closed: usar interfaces e abstrações para extensibilidade
- **L** — Liskov Substitution: subclasses devem ser substituíveis pela classe pai
- **I** — Interface Segregation: interfaces pequenas e específicas, não genéricas
- **D** — Dependency Inversion: depender de abstrações (interfaces), não de implementações concretas

### Design Patterns

- **Repository** — obrigatório para acesso a dados
- **Strategy** — quando há múltiplas variações de um comportamento (ex: cálculo de frete, métodos de pagamento)
- **Factory** — para criação complexa de objetos
- **Observer/Event** — usar Symfony Event Dispatcher para desacoplar side effects
- Não forçar patterns onde não são necessários — simplicidade primeiro

### Boas Práticas Gerais

- Tipagem forte: type hints em parâmetros, retornos e propriedades
- Enums nativos do PHP para valores fixos (status, tipos, etc.)
- Custom Exceptions (`src/Exception/`) com mensagens claras para erros de domínio
- Nomear classes, métodos e variáveis de forma descritiva — o código deve ser autoexplicativo
- Evitar herança profunda — preferir composição

## Environment

- `.env` — base config (APP_ENV, APP_SECRET, DEFAULT_URI)
- `.env.dev` — dev secrets
- `.env.test` — test config
- `.env.local` — local overrides (gitignored)

## Testing

- PHPUnit with strict deprecation/notice/warning failure
- Bootstrap in `tests/bootstrap.php` loads dotenv
- Run with `bin/phpunit` or `docker compose exec php bin/phpunit`

## Docker

- **Dev:** hot reload via watch mode, XDebug enabled, source mounted
- **Prod:** slim Debian image, rootless `www-data`, opcache preloading
- Ports: 80, 443, 443/UDP (HTTP/3)
