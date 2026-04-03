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

---

## Event-Driven Architecture Visualizer & Simulator

### Visão Geral do Projeto

Ferramenta educacional/de design onde o usuário modela arquiteturas orientadas a eventos usando um canvas visual (Vue Flow no frontend). Nós representam microserviços, filas, tópicos e bancos de dados. Edges representam fluxo de mensagens. O backend Symfony orquestra simulações reais usando RabbitMQ e transmite o estado em tempo real via Mercure.

### O Que Precisa Ser Instalado/Configurado

#### 1. RabbitMQ (Docker)

Adicionar serviço RabbitMQ ao `compose.yaml`:

```yaml
rabbitmq:
  image: rabbitmq:3-management-alpine
  ports:
    - "5672:5672"    # AMQP
    - "15672:15672"  # Management UI
  environment:
    RABBITMQ_DEFAULT_USER: guest
    RABBITMQ_DEFAULT_PASS: guest
  volumes:
    - rabbitmq_data:/var/lib/rabbitmq
  healthcheck:
    test: rabbitmq-diagnostics -q ping
    interval: 10s
    timeout: 5s
    retries: 5
```

Adicionar `rabbitmq_data` aos volumes do compose.

#### 2. Symfony Messenger + AMQP Transport

```bash
docker compose exec php composer require symfony/messenger symfony/amqp-messenger
```

Isso instala o componente Messenger e o transport AMQP. A extensão `ext-amqp` precisa ser adicionada ao Dockerfile.

No Dockerfile, adicionar `amqp` à lista de extensões PHP:

```dockerfile
install-php-extensions amqp
```

#### 3. Variáveis de Ambiente

Adicionar ao `.env`:

```env
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
RABBITMQ_DSN=amqp://guest:guest@rabbitmq:5672/%2f
```

#### 4. Configuração do Messenger (`config/packages/messenger.yaml`)

```yaml
framework:
  messenger:
    transports:
      simulation:
        dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        options:
          exchange:
            name: simulation
            type: topic
          queues:
            simulation_events:
              binding_keys: ['simulation.*']
    routing:
      'App\Message\SimulationMessage': simulation
```

### Arquitetura Backend (Entidades & Services)

#### Entidades Principais

- **Topology** — Representa uma topologia/arquitetura completa (nome, descrição, timestamps)
- **TopologyNode** — Nó no grafo (tipo: microservice|queue|topic|database, posição x/y, config JSON)
- **TopologyEdge** — Conexão entre nós (source_node, target_node, latência simulada, taxa de falha)
- **Simulation** — Uma execução de simulação (topology, status: pending|running|completed|failed, timestamps)
- **SimulationEvent** — Evento individual durante simulação (simulation, source_node, target_node, payload, status: sent|delivered|failed, latência real, timestamp)

#### Services

- **TopologyService** — CRUD de topologias com nós e edges
- **SimulationService** — Orquestra a simulação: lê o grafo, publica mensagens no RabbitMQ seguindo a topologia, registra eventos
- **SimulationPublisher** — Publica atualizações em tempo real via Mercure (estado de cada mensagem fluindo pelo grafo)
- **MetricsService** — Calcula throughput, latência média, taxa de falha por nó/edge

#### Controllers (API REST)

- `TopologyController` — CRUD topologias (`/api/topologies`)
- `SimulationController` — Iniciar/parar/status simulações (`/api/simulations`)
- `MetricsController` — Métricas de simulação (`/api/simulations/{id}/metrics`)

#### Messages (Symfony Messenger)

- `SimulationMessage` — Mensagem que representa um evento fluindo de nó a nó na topologia
- `SimulationMessageHandler` — Processa a mensagem, aplica latência simulada, decide falha aleatória, publica próximos hops no grafo, e notifica frontend via Mercure

### Mercure (Tópicos de Real-Time)

Tópicos usados para streaming de simulação:

- `simulation/{simulationId}/events` — Cada evento de mensagem fluindo pelo grafo
- `simulation/{simulationId}/metrics` — Métricas atualizadas periodicamente
- `simulation/{simulationId}/status` — Mudanças de status da simulação

### Fluxo da Simulação

1. Frontend envia POST `/api/simulations` com `topologyId`
2. Backend cria `Simulation`, lê nós/edges da `Topology`
3. Para cada nó "produtor" (sem edges de entrada), publica `SimulationMessage` no RabbitMQ
4. `SimulationMessageHandler` consome a mensagem:
   a. Aplica `sleep()` com latência simulada do edge
   b. Sorteia falha com base na taxa de falha do edge
   c. Registra `SimulationEvent` no banco
   d. Publica update via Mercure para o frontend
   e. Se não falhou, publica `SimulationMessage` para os próximos nós do grafo (edges de saída)
5. Frontend recebe eventos via EventSource e anima pulsos nas edges correspondentes
