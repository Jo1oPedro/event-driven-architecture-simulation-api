# Mercure — Real-Time no Symfony

## O que e Mercure?

Mercure e um **protocolo e servidor de real-time** criado pelo mesmo autor do Symfony (Kevin Dunglas).
Ele permite que o backend **envie dados pro frontend em tempo real**, sem que o frontend fique
fazendo polling.

### Comparando com o que voce ja conhece (Laravel):

```
 LARAVEL                              SYMFONY + MERCURE
 ===================================  ===================================
 Laravel Broadcasting                 Mercure Protocol
 Pusher / Laravel Websockets / Soketi Mercure Hub (embutido no Caddy)
 Laravel Echo (JS client)             EventSource nativa do browser
 WebSocket (bidirecional)             SSE - Server-Sent Events (unidirecional)
```

**Diferenca fundamental:**
- **WebSocket** (Pusher/Soketi): conexao bidirecional — cliente e servidor enviam dados
- **SSE/Mercure**: conexao unidirecional — **so o servidor envia dados pro cliente**

Na maioria dos casos de API (notificacoes, atualizacao de status, feed em tempo real),
voce so precisa do servidor→cliente. Mercure e perfeito pra isso.

---

## Como funciona?

```
┌─────────┐          ┌──────────────────┐          ┌─────────┐
│   Vue   │          │   Mercure Hub    │          │ Symfony  │
│ (front) │          │ (dentro do Caddy)│          │  (API)   │
└────┬────┘          └────────┬─────────┘          └────┬────┘
     │                        │                         │
     │  1. Subscribe (SSE)    │                         │
     │───────────────────────>│                         │
     │   GET /.well-known/    │                         │
     │       mercure?topic=   │                         │
     │       /orders/{id}     │                         │
     │                        │                         │
     │   Conexao fica aberta  │                         │
     │   (streaming)          │                         │
     │                        │                         │
     │                        │  2. Publish (POST)      │
     │                        │<────────────────────────│
     │                        │   POST /.well-known/    │
     │                        │        mercure          │
     │                        │   topic=/orders/42      │
     │                        │   data={"status":"paid"}│
     │                        │                         │
     │  3. Recebe o evento    │                         │
     │<───────────────────────│                         │
     │   data: {"status":     │                         │
     │          "paid"}       │                         │
     │                        │                         │
```

**Resumo:**
1. O **frontend** se inscreve num topico (topic) via SSE
2. O **backend** publica uma mensagem nesse topico via HTTP POST
3. O **Mercure Hub** repassa a mensagem pra todos os inscritos

---

## Onde o Mercure esta configurado neste projeto?

### 1. Caddyfile — O Hub roda dentro do Caddy

```
mercure {
    publisher_jwt {env.MERCURE_PUBLISHER_JWT_KEY}   # Chave pra backend publicar
    subscriber_jwt {env.MERCURE_SUBSCRIBER_JWT_KEY} # Chave pra frontend receber
    anonymous                                        # Permite inscritos anonimos
    subscriptions                                    # Habilita API de subscriptions
}
```

O Mercure nao e um servico separado — ele roda como **modulo do Caddy**, no mesmo container
do PHP. Diferente do Pusher/Soketi que sao servicos externos.

### 2. compose.yaml — Variaveis de ambiente

```yaml
MERCURE_PUBLISHER_JWT_KEY: ${CADDY_MERCURE_JWT_SECRET}   # JWT pra autenticar publicacoes
MERCURE_SUBSCRIBER_JWT_KEY: ${CADDY_MERCURE_JWT_SECRET}  # JWT pra autenticar inscricoes
MERCURE_URL: http://php/.well-known/mercure              # URL interna (backend → hub)
MERCURE_PUBLIC_URL: https://localhost/.well-known/mercure # URL publica (frontend → hub)
MERCURE_JWT_SECRET: ${CADDY_MERCURE_JWT_SECRET}          # Secret compartilhado
```

**Duas URLs diferentes:**
- `MERCURE_URL` — usada pelo Symfony internamente (dentro do Docker network)
- `MERCURE_PUBLIC_URL` — usada pelo frontend/browser (acesso externo)

---

## Utilidade dentro de uma API

Sim, Mercure e **extremamente util** em APIs. Alguns casos reais:

| Caso de uso                  | Sem Mercure (polling)          | Com Mercure (real-time)        |
|------------------------------|--------------------------------|--------------------------------|
| Status de pedido             | Frontend faz GET a cada 5s     | Backend avisa quando muda      |
| Notificacoes                 | Frontend faz GET a cada 10s    | Chega instantaneamente         |
| Chat                         | Polling constante              | Mensagem chega em tempo real   |
| Dashboard com metricas       | Refresh manual ou polling      | Atualiza automaticamente       |
| Fila de processamento        | Verifica status periodicamente | Recebe update quando termina   |
| Estoque / disponibilidade    | Dados podem estar defasados    | Atualiza ao mudar              |

**Vantagem sobre polling:** menos requests, menos carga no servidor, dados em tempo real.

---

## Exemplo pratico: Notificacao de pedido

### 1. Instalar o bundle do Mercure

```bash
docker compose exec php composer require symfony/mercure-bundle
```

### 2. Service que publica eventos (backend)

```php
// src/Service/OrderNotificationService.php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class OrderNotificationService
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function notifyStatusChange(int $orderId, string $status): void
    {
        $update = new Update(
            // Topic: quem esta inscrito em /orders/42 recebe
            topics: "https://example.com/orders/{$orderId}",

            // Dados enviados pro frontend (JSON)
            data: json_encode([
                'orderId' => $orderId,
                'status' => $status,
                'updatedAt' => (new \DateTimeImmutable())->format('c'),
            ]),
        );

        $this->hub->publish($update);
    }
}
```

### 3. Controller que usa o service

```php
// src/Controller/OrderController.php

namespace App\Controller;

use App\Service\OrderNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/api/orders/{id}/status', methods: ['PATCH'])]
    public function updateStatus(
        int $id,
        OrderNotificationService $notificationService,
    ): JsonResponse {
        // ... atualiza o status no banco via service ...

        // Notifica todos os inscritos em tempo real
        $notificationService->notifyStatusChange($id, 'paid');

        return $this->json(['message' => 'Status atualizado']);
    }
}
```

### 4. Frontend Vue recebendo o evento

```javascript
// Nenhuma lib extra necessaria — EventSource e nativo do browser

const orderId = 42
const url = new URL('https://localhost/.well-known/mercure')
url.searchParams.append('topic', `https://example.com/orders/${orderId}`)

const eventSource = new EventSource(url)

eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data)
  console.log('Pedido atualizado:', data)
  // { orderId: 42, status: "paid", updatedAt: "2026-04-03T..." }

  // Atualiza a UI reativa do Vue
  orderStatus.value = data.status
}

// Fechar quando o componente desmontar
// eventSource.close()
```

**Repare:** no frontend nao precisa instalar nenhuma biblioteca. `EventSource` e uma API
nativa do browser. No Laravel voce precisaria do Laravel Echo + Pusher JS SDK.

---

## Mercure vs WebSocket vs Polling

```
                    Mercure (SSE)    WebSocket         Polling
                    ─────────────    ─────────         ───────
Direcao             Server→Client    Bidirecional      Client→Server
Complexidade        Baixa            Alta              Muito baixa
Reconexao auto      Sim (nativo)     Manual            N/A
Funciona com HTTP/2 Sim              Nao               Sim
Proxy/CDN friendly  Sim              Problematico      Sim
Lib no frontend     Nenhuma (nativo) Precisa lib       Nenhuma
Tempo real          Sim              Sim               Nao (delay)
Carga no servidor   Baixa            Media             Alta
Ideal pra           Notificacoes,    Chat bidirecional Fallback,
                    feeds, status    jogos, collab     compatibilidade
```

### Quando usar Mercure:
- Notificacoes, atualizacao de status, feeds — **maioria dos casos de API**
- Quando so o servidor precisa empurrar dados pro cliente

### Quando NAO usar Mercure:
- Chat onde o cliente tambem envia mensagens em tempo real (WebSocket e melhor)
- Jogos multiplayer com comunicacao constante bidirecional

---

## Topicos (Topics)

Topicos sao como "canais" (similar aos channels do Laravel Broadcasting):

```php
// Inscricao global — recebe TODOS os pedidos
$update = new Update('https://example.com/orders');

// Inscricao especifica — recebe so o pedido 42
$update = new Update('https://example.com/orders/42');

// Multiplos topicos de uma vez
$update = new Update([
    'https://example.com/orders/42',
    'https://example.com/users/7/notifications',
]);
```

No frontend:

```javascript
const url = new URL('https://localhost/.well-known/mercure')

// Inscrever em um topico
url.searchParams.append('topic', 'https://example.com/orders/42')

// Ou em varios
url.searchParams.append('topic', 'https://example.com/orders/42')
url.searchParams.append('topic', 'https://example.com/users/7/notifications')

// Ou com wildcard — todos os pedidos
url.searchParams.append('topic', 'https://example.com/orders/{id}')
```

---

## Publicacoes privadas (autenticacao)

Por padrao, com `anonymous` habilitado, qualquer um pode se inscrever. Para topicos privados:

```php
// No backend — marca como privado
$update = new Update(
    topics: 'https://example.com/users/7/notifications',
    data: json_encode(['message' => 'Seu pedido foi aprovado']),
    private: true,  // So inscritos autenticados recebem
);
```

O frontend precisa enviar um JWT cookie com as permissoes. O Symfony Mercure Bundle
gera esse cookie automaticamente quando configurado.

---

## Resumo

```
┌────────────────────────────────────────────────────────┐
│                    Neste projeto                       │
│                                                        │
│  Mercure Hub = modulo dentro do Caddy (mesmo container)│
│  Endpoint = https://localhost/.well-known/mercure      │
│  Protocolo = SSE (Server-Sent Events)                  │
│  Autenticacao = JWT                                    │
│  Frontend = EventSource nativa (zero dependencias)     │
│                                                        │
│  Equivalente Laravel:                                  │
│    Broadcasting + Pusher + Echo                        │
│    Tudo isso substituido por Mercure + EventSource     │
└────────────────────────────────────────────────────────┘
```
