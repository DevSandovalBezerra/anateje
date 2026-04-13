# Breakpoint Report — Migração de Eventos (api/v1/events.php → api/v2)

Data: 2026-04-13

## Contexto

O endpoint legado de eventos está em [events.php](file:///C:/wamp64/www/anateje/api/v1/events.php) e atende tanto a área do associado quanto o admin via `?action=...`.

No front:

- Admin usa [eventos.php](file:///C:/wamp64/www/anateje/frontend/admin/eventos.php) chamando `api/v1/events.php?action=admin_*`
- Associado usa [meus_eventos.php](file:///C:/wamp64/www/anateje/frontend/associado/meus_eventos.php) chamando `api/v1/events.php?action=list|detail|register|cancel`

## Estado Atual (o que já existe)

- Estrutura criada do módulo em [events-module](file:///C:/wamp64/www/anateje/modules/events-module)
- Implementações já presentes:
  - [Helpers.php](file:///C:/wamp64/www/anateje/modules/events-module/src/Application/Helpers.php) (funções utilitárias migradas do legado)
  - Handlers criados em [Handlers](file:///C:/wamp64/www/anateje/modules/events-module/src/Http/Handlers):
    - ListEventsHandler
    - DetailEventHandler
    - RegisterEventHandler
    - CancelEventHandler
    - AdminListEventsHandler
    - AdminSaveEventHandler
    - AdminDeleteEventHandler
    - AdminBulkStatusEventsHandler

## Estado Atual (pendências / pontos quebrados)

### 1) O módulo NÃO está integrado ao bootstrap do v2

- Ainda não está registrado em [modules.php](file:///C:/wamp64/www/anateje/config/modules.php).
- Ainda não existe `composer.json` dentro de `modules/events-module/`, então ele não pode ser `require`-ado via path repository como os demais módulos.
- O root [composer.json](file:///C:/wamp64/www/anateje/composer.json) não referencia `anateje/events-module`.

### 2) O padrão do módulo está divergente do padrão dos módulos já migrados

Os módulos já migrados (ex.: [benefits-module Module.php](file:///C:/wamp64/www/anateje/modules/benefits-module/src/Module.php)) implementam `RouteProvider` e entregam um array de `Route` com paths completos `/api/v2/...`.

O Kernel do v2 ([Kernel.php](file:///C:/wamp64/www/anateje/src/Http/Kernel.php)) só carrega rotas a partir desses `Route` objects. Ele não chama `registerRoutes()` / `registerDependencies()` como no arquivo atual do módulo de eventos ([events-module Module.php](file:///C:/wamp64/www/anateje/modules/events-module/src/Module.php)).

Resultado: mesmo que o código compile, as rotas do events-module não entram no dispatcher.

### 3) Handlers referenciados, mas ainda não existem

Em [events-module Module.php](file:///C:/wamp64/www/anateje/modules/events-module/src/Module.php), existem referências a handlers que ainda não foram criados:

- `AdminRegistrationsEventHandler`
- `AdminCheckinEventHandler`
- `AdminRegistrationStatusEventHandler`
- `AdminPromoteWaitlistEventHandler`
- `AdminExportCsvEventHandler`

### 4) Envelope de resposta está incompatível com o client JS

O client JS padrão ([anateje-api.js](file:///C:/wamp64/www/anateje/assets/js/anateje-api.js)) exige:

- `json.ok === true`
- retorna `json.data`

Os handlers atuais de eventos retornam payload sem `data` (ex.: `{'ok': true, 'events': ...}`), o que quebra consumo via `window.anatejeApi(...)` quando/ao migrar o front.

## Alvo de Migração (equivalência de ações)

Legado → Equivalente sugerido no v2:

- `GET  /api/v1/events.php?action=list` → `GET  /api/v2/events`
- `GET  /api/v1/events.php?action=detail&id=123` → `GET  /api/v2/events/123`
- `POST /api/v1/events.php?action=register` → `POST /api/v2/events/register`
- `POST /api/v1/events.php?action=cancel` → `POST /api/v2/events/cancel`

Admin:

- `GET  /api/v1/events.php?action=admin_list` → `GET  /api/v2/admin/events`
- `POST /api/v1/events.php?action=admin_save` → `POST /api/v2/admin/events/save`
- `POST /api/v1/events.php?action=admin_delete` → `POST /api/v2/admin/events/delete`
- `POST /api/v1/events.php?action=admin_bulk_status` → `POST /api/v2/admin/events/bulk-status`
- `GET  /api/v1/events.php?action=admin_registrations&id=123&...` → `GET  /api/v2/admin/events/123/registrations?...`
- `POST /api/v1/events.php?action=admin_checkin` → `POST /api/v2/admin/events/checkin`
- `POST /api/v1/events.php?action=admin_registration_status` → `POST /api/v2/admin/events/registration-status`
- `POST /api/v1/events.php?action=admin_promote_waitlist` → `POST /api/v2/admin/events/promote-waitlist`
- `GET  /api/v1/events.php?action=admin_export_csv&id=123&...` → `GET  /api/v2/admin/events/123/export-csv?...`

## Continuação (passo a passo claro)

### Passo 1 — Trazer o events-module para o mesmo padrão dos módulos existentes

- Alterar `events-module/src/Module.php` para implementar `Anateje\Contracts\RouteProvider` e retornar `Route[]`.
- Padronizar paths com prefixo `/api/v2` (ex.: `/api/v2/events`, `/api/v2/admin/events/...`).
- Remover acoplamento a `FastRoute\RouteCollector` e `ContainerInterface` dentro do módulo (os handlers devem ser resolvidos via autowire + dependências já registradas no `bootstrap/modula.php`).

Referências úteis:

- Exemplo de módulo simples: [benefits-module Module.php](file:///C:/wamp64/www/anateje/modules/benefits-module/src/Module.php)
- Tipo de rota esperado: [Route.php](file:///C:/wamp64/www/anateje/modules/contracts/src/Route.php)
- Como o Kernel consome rotas: [Kernel.php](file:///C:/wamp64/www/anateje/src/Http/Kernel.php)

### Passo 2 — Ajustar handlers para o envelope padrão do v2

- Injetar `Anateje\Contracts\ResponseFactory` nos handlers e responder sempre com:
  - sucesso: `['ok' => true, 'data' => ...]`
  - erro: `['ok' => false, 'error' => ['code' => ..., 'message' => ...]]`
- Usar o mesmo padrão dos handlers existentes (ex.: [ListMembersHandler.php](file:///C:/wamp64/www/anateje/modules/members-module/src/Http/ListMembersHandler.php)).

### Passo 3 — Completar os 5 handlers admin faltantes

Implementar equivalentes do legado para:

- `admin_registrations`
- `admin_checkin`
- `admin_registration_status`
- `admin_promote_waitlist`
- `admin_export_csv`

Reaproveitar ao máximo:

- filtros/paginação em `Helpers::parseRegistrationFilters()` e `Helpers::parsePagination()`
- SQL `Helpers::registrationWhereSql()`
- promoção de fila `Helpers::promoteWaitlisted()`

### Passo 4 — Integrar o módulo via Composer + registro em config/modules.php

- Criar `modules/events-module/composer.json` no mesmo padrão de `members-module` (name, version, autoload PSR-4).
- Adicionar `anateje/events-module` ao `require` do root [composer.json](file:///C:/wamp64/www/anateje/composer.json).
- Rodar `composer update` (ou `composer require anateje/events-module:*`) e `composer dump-autoload`.
- Registrar `Anateje\EventsModule\Module::class` em [config/modules.php](file:///C:/wamp64/www/anateje/config/modules.php).

### Passo 5 — Troca gradual no frontend (strangler)

O front ainda consome `api/v1`. A troca recomendada é por tela:

- `frontend/associado/meus_eventos.php`: migrar chamadas de `api/v1/events.php` para os novos endpoints do v2
- `frontend/admin/eventos.php`: migrar `admin_*` para `/api/v2/admin/events/...`

Checklist de compatibilidade do client:

- `anatejeApi()` espera `json.data`
- `anatejeApi()` manda JSON no body com `Content-Type: application/json`
  - Se `ServerRequestCreator` não preencher `getParsedBody()` para JSON, manter parsing via `json_decode((string)$request->getBody(), true)` nos handlers que recebem POST (ou introduzir um parser global depois).

## Próximos Módulos (sequência sugerida)

1) Campanhas

- Legado: [campaigns.php](file:///C:/wamp64/www/anateje/api/v1/campaigns.php)
- Admin UI: [campanhas.php](file:///C:/wamp64/www/anateje/frontend/admin/campanhas.php)
- Estratégia: repetir padrão do `benefits-module` (CRUD admin), mantendo endpoints `/api/v2/campaigns` e `/api/v2/campaigns/delete`.

2) Comunicados (posts)

- Legado: [posts.php](file:///C:/wamp64/www/anateje/api/v1/posts.php)
- Admin UI: [comunicados.php](file:///C:/wamp64/www/anateje/frontend/admin/comunicados.php)
- Associado: [comunicados.php](file:///C:/wamp64/www/anateje/frontend/associado/comunicados.php)

## Artefatos temporários gerados durante a migração

- [generate_handlers.php](file:///C:/wamp64/www/anateje/generate_handlers.php)
- [generate_handlers_admin.php](file:///C:/wamp64/www/anateje/generate_handlers_admin.php)

Eles foram usados apenas para acelerar a geração inicial de handlers; após concluir a migração de eventos, podem ser removidos para reduzir ruído.

