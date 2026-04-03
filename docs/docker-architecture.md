# Arquitetura Docker do Projeto — Guia para quem vem do Laravel/Octane

## Visao Geral: Comparativo Laravel vs Symfony

```
 LARAVEL + OCTANE (o que voce conhece)        SYMFONY + FRANKENPHP (este projeto)
 ==========================================   ==========================================
 Nginx/Caddy (proxy reverso)                  Caddy (embutido no FrankenPHP)
        |                                            |
 PHP-FPM ou Octane (Swoole/RoadRunner)        FrankenPHP (worker mode)
        |                                            |
 Laravel Framework                            Symfony Framework
        |                                            |
 Banco de dados                               Banco de dados
```

**A grande diferenca:** No Laravel com Octane voce tem Nginx + Octane (Swoole/RoadRunner) como
processos separados. Aqui, o FrankenPHP **embute o PHP dentro do Caddy** — e um unico binario
que e servidor web + runtime PHP ao mesmo tempo.

---

## O que e FrankenPHP?

Pense no FrankenPHP como o equivalente do Octane, mas em vez de ser um pacote PHP que roda
sobre Swoole/RoadRunner, ele e um **modulo compilado dentro do Caddy**.

```
 Octane (Laravel)                    FrankenPHP (Symfony)
 ========================           ========================
 Caddy/Nginx (proxy)                Caddy (tudo junto)
      |                                  |
 Swoole/RoadRunner (runtime)        FrankenPHP (modulo do Caddy)
      |                                  |
 PHP em memoria (workers)           PHP em memoria (workers)
```

Ambos fazem a mesma coisa: **mantêm o PHP carregado em memoria** entre requests, evitando o
bootstrap a cada request como no PHP-FPM tradicional.

---

## Estrutura dos Arquivos Docker

```
projeto/
├── Dockerfile                    # Multi-stage: base, dev, prod
├── compose.yaml                  # Servicos principais (portas, volumes, env)
├── compose.override.yaml         # Overrides de dev (watch, xdebug, source mount)
├── compose.prod.yaml             # Overrides de producao
└── frankenphp/
    ├── Caddyfile                 # Config do servidor web (equivalente ao nginx.conf)
    ├── docker-entrypoint.sh      # Script de inicializacao do container
    └── conf.d/
        ├── 10-app.ini            # PHP config base (opcache, timezone)
        ├── 20-app.dev.ini        # PHP config dev (xdebug)
        └── 20-app.prod.ini       # PHP config prod (preloading, sem timestamps)
```

---

## Dockerfile: Multi-Stage Build

O Dockerfile tem **4 estagios**. Pense como camadas de uma cebola:

```
┌─────────────────────────────────────────────────┐
│  1. frankenphp_base                             │
│     Imagem base com PHP 8.5, Composer,          │
│     extensoes (APCu, Intl, Opcache, Zip),       │
│     Caddyfile e entrypoint                      │
│                                                 │
│  ┌─────────────────────┐  ┌──────────────────┐  │
│  │ 2. frankenphp_dev   │  │ 3. prod_builder  │  │
│  │    Herda da base    │  │    Herda da base │  │
│  │    + XDebug         │  │    + composer     │  │
│  │    + ferramentas    │  │      install      │  │
│  │    + watch mode     │  │      --no-dev    │  │
│  │                     │  │    + dump-env     │  │
│  │  USADO EM DEV <<<  │  │    + opcache      │  │
│  └─────────────────────┘  │      preload     │  │
│                           └────────┬─────────┘  │
│                                    │             │
│                           ┌────────▼─────────┐  │
│                           │ 4. frankenphp_   │  │
│                           │    prod          │  │
│                           │  Debian slim     │  │
│                           │  Copia so o      │  │
│                           │  necessario do   │  │
│                           │  builder         │  │
│                           │  Roda como       │  │
│                           │  www-data        │  │
│                           │  (rootless)      │  │
│                           │                  │  │
│                           │ USADO EM PROD<<<│  │
│                           └──────────────────┘  │
└─────────────────────────────────────────────────┘
```

**Comparando com Laravel:**
- Em dev, e como rodar `php artisan octane:start --watch`
- Em prod, e como fazer o deploy otimizado com `php artisan optimize` + Octane sem watch

---

## Compose Files — Como os Ambientes Funcionam

O Docker Compose usa um sistema de **camadas**. O `compose.override.yaml` e o `compose.prod.yaml`
**nunca se misturam** — sao mutuamente exclusivos.

### Em dev (comportamento padrao):

```bash
docker compose up
```

O Docker mergeia automaticamente:

```
compose.yaml  +  compose.override.yaml
  (base)            (dev — carregado automaticamente)
```

O `compose.override.yaml` e um nome magico — o Docker Compose **sempre** carrega ele
junto com `compose.yaml` quando voce nao especifica arquivos com `-f`.

### Em prod (explicito):

```bash
docker compose -f compose.yaml -f compose.prod.yaml up
```

Aqui ele mergeia:

```
compose.yaml  +  compose.prod.yaml
  (base)            (prod — precisa especificar com -f)
```

O `compose.override.yaml` **NAO e carregado** quando voce passa `-f` manualmente.

### Resumo:

```
┌──────────────────────────────────────────────────────────┐
│                    compose.yaml (BASE)                   │
│         Portas, env vars, volumes compartilhados         │
│                                                          │
│     ┌──────────────────┐    ┌──────────────────────┐     │
│     │  compose.        │    │  compose.prod.yaml   │     │
│     │  override.yaml   │    │                      │     │
│     │                  │    │  - imagem slim        │     │
│     │  - source mount  │    │  - rootless           │     │
│     │  - watch mode    │    │  - opcache preload    │     │
│     │  - xdebug        │    │  - APP_SECRET         │     │
│     │  - hot reload    │    │    obrigatorio        │     │
│     │                  │    │                      │     │
│     │  docker compose  │    │  docker compose -f    │     │
│     │  up (automatico) │    │  compose.yaml -f      │     │
│     │                  │    │  compose.prod.yaml up │     │
│     └──────────────────┘    └──────────────────────┘     │
│          DEV                         PROD                │
│     Nunca se misturam entre si                           │
└──────────────────────────────────────────────────────────┘
```

---

## compose.yaml — Servico Principal

```yaml
services:
  php:
    environment:
      SERVER_NAME: ${SERVER_NAME:-localhost}, php:80
      #            ^^^^^^^^^^^^^^^^^^^^^^^^  ^^^^^^
      #            HTTPS externo              HTTP interno (healthcheck)
```

### Portas expostas:

```
 Host (sua maquina)          Container (Docker)
 ====================        ====================
 :80   ──────────────────►   :80   (HTTP → redireciona pra HTTPS)
 :443  ──────────────────►   :443  (HTTPS)
 :443/udp ───────────────►   :443  (HTTP/3 via QUIC)
```

### Volumes:

```
 caddy_data    → /data     # Certificados TLS gerados pelo Caddy
 caddy_config  → /config   # Configuracao persistente do Caddy
```

---

## compose.override.yaml — Dev Mode

Este arquivo e carregado **automaticamente** em dev (Docker Compose mergeia com compose.yaml):

```yaml
services:
  php:
    build:
      target: frankenphp_dev           # Usa o estagio DEV do Dockerfile
    volumes:
      - ./:/app                        # Monta seu codigo fonte no container
    environment:
      FRANKENPHP_WORKER_CONFIG: watch  # Reinicia workers quando arquivos mudam
      FRANKENPHP_SITE_CONFIG: hot_reload
      XDEBUG_MODE: develop
```

**Equivalente no Laravel:**
- `volumes: ./:/app` = seu codigo local sincronizado (como bind mount normal)
- `FRANKENPHP_WORKER_CONFIG: watch` = `php artisan octane:start --watch`

---

## Caddyfile — O "nginx.conf" do Projeto

```
┌──────────────────────────────────────────────────────────┐
│  {$SERVER_NAME:localhost}                                │
│  (Caddy escuta nesse dominio)                            │
│                                                          │
│  ┌─ root /app/public                                     │
│  │  (equivalente ao root do nginx apontando pra public/) │
│  │                                                       │
│  ├─ encode zstd br gzip                                  │
│  │  (compressao automatica)                              │
│  │                                                       │
│  ├─ mercure { ... }                                      │
│  │  (hub de real-time, tipo Laravel Broadcasting/Pusher) │
│  │                                                       │
│  ├─ vulcain                                              │
│  │  (preloading de recursos via HTTP/2 Push)             │
│  │                                                       │
│  ├─ @phpRoute → rewrite → index.php                      │
│  │  (tudo que nao e arquivo estatico vai pro Symfony)    │
│  │                                                       │
│  ├─ php @frontController { worker { watch } }            │
│  │  (FrankenPHP processa o PHP em worker mode)           │
│  │                                                       │
│  └─ file_server { hide *.php }                           │
│     (serve arquivos estaticos, esconde .php)             │
└──────────────────────────────────────────────────────────┘
```

**Fluxo de um request:**

```
Browser GET /api/ping
       │
       ▼
   Caddy recebe (porta 443, HTTPS)
       │
       ├── E arquivo estatico? (css, js, imagem)
       │        SIM → file_server serve direto
       │        NAO ↓
       │
       ├── E rota do Mercure? (/.well-known/mercure)
       │        SIM → hub Mercure processa
       │        NAO ↓
       │
       ▼
   Rewrite para index.php
       │
       ▼
   FrankenPHP worker (PHP em memoria)
       │
       ▼
   Symfony Kernel processa
       │
       ▼
   Response volta pro browser
```

---

## HTTPS Local — Como Funciona?

Essa e provavelmente a parte que mais te confunde. No Laravel, voce acessa `http://localhost:8000`
e pronto. Aqui, o Caddy **forca HTTPS ate em localhost**.

### O mecanismo:

```
 1. Caddy inicia e ve SERVER_NAME=localhost
         │
         ▼
 2. Caddy gera automaticamente:
    - Uma CA (Certificate Authority) local
    - Um certificado TLS para "localhost"
    - Armazena em /data (volume caddy_data)
         │
         ▼
 3. Qualquer request HTTP (:80) recebe
    308 Permanent Redirect → HTTPS (:443)
         │
         ▼
 4. Browser acessa https://localhost
    - Certificado e auto-assinado
    - Browser mostra aviso (normal em dev)
    - Voce aceita uma vez e funciona
```

**Por que HTTPS em dev?**
- Mercure (real-time) precisa de HTTPS em alguns cenarios
- HTTP/3 so funciona com HTTPS
- Testa em dev o mais proximo possivel de producao
- Service Workers e algumas Web APIs exigem HTTPS

**No volume `caddy_data`** ficam os certificados. Por isso eles persistem entre restarts
do container — o Caddy nao gera novos certificados toda vez.

### O redirect que causou seu problema de CORS:

```
 Vue (http://localhost:5173)
       │
       │  fetch("http://localhost/api/ping")
       ▼
 Caddy (:80) responde: 308 → https://localhost/api/ping
       │
       ▼
 Browser bloqueia: o redirect nao tem headers CORS
 Erro: "No 'Access-Control-Allow-Origin' header"
```

**Solucao:** O Vue precisa chamar `https://localhost/api/ping` (e nao `http://`).

---

## docker-entrypoint.sh — Inicializacao

Quando o container sobe, este script roda antes do FrankenPHP:

```
 Container inicia
       │
       ▼
 vendor/ esta vazio?
       SIM → composer install
       NAO ↓
       │
       ▼
 Mostra versao do Symfony (bin/console -V)
       │
       ▼
 Tem DATABASE_URL no .env?
       SIM → Espera o banco ficar pronto (ate 60 tentativas)
            → Roda migrations automaticamente
       NAO ↓
       │
       ▼
 "PHP app ready!"
       │
       ▼
 Executa: frankenphp run --config /etc/frankenphp/Caddyfile
```

**Equivalente no Laravel:** e como se voce tivesse um script que roda
`composer install && php artisan migrate && php artisan octane:start` automaticamente.

---

## Configs PHP (conf.d/)

### 10-app.ini (base, todos os ambientes)

| Config                      | O que faz                                        |
|-----------------------------|--------------------------------------------------|
| `expose_php = 0`            | Esconde header "X-Powered-By: PHP"               |
| `opcache.memory_consumption`| 256MB de cache para bytecode compilado            |
| `realpath_cache_size`       | 4MB de cache para resolucao de caminhos de arquivo|
| `apc.enable_cli`            | Habilita APCu no CLI (util pra workers)           |

### 20-app.dev.ini (apenas dev)

| Config                        | O que faz                                   |
|-------------------------------|---------------------------------------------|
| `xdebug.client_host`         | Conecta XDebug de volta ao host (sua IDE)   |

### 20-app.prod.ini (apenas prod)

| Config                        | O que faz                                        |
|-------------------------------|--------------------------------------------------|
| `opcache.preload`             | Pre-carrega classes na memoria ao iniciar         |
| `opcache.validate_timestamps` | Nao checa se arquivos mudaram (maximo performance)|

---

## Resumo: Mapeamento Laravel ↔ Este Projeto

| Conceito              | Laravel + Octane              | Este Projeto                       |
|-----------------------|-------------------------------|------------------------------------|
| Servidor web          | Nginx (separado)              | Caddy (embutido no FrankenPHP)     |
| Runtime PHP           | Swoole / RoadRunner           | FrankenPHP (modulo do Caddy)       |
| Worker mode           | `octane:start`                | `FRANKENPHP_WORKER_CONFIG`         |
| Watch/hot reload      | `octane:start --watch`        | `FRANKENPHP_WORKER_CONFIG: watch`  |
| HTTPS local           | Nao tem (manual)              | Automatico via Caddy               |
| Real-time             | Broadcasting + Pusher/Soketi  | Mercure (embutido no Caddy)        |
| Config servidor       | nginx.conf / vhost            | Caddyfile                          |
| Entrypoint            | public/index.php              | public/index.php                   |
| Routing               | routes/web.php, routes/api.php| Atributos nos controllers          |
| Debug                 | Telescope + Xdebug            | Symfony Profiler + XDebug          |
| Migrations auto       | Manual                        | Automatico no entrypoint           |
| Container prod        | Geralmente custom              | Debian slim, rootless, otimizado   |
