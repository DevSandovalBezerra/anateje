# Plano detalhado de adaptacao do ANATEJE para o template “modula” (monolito modular)

Data: 2026-04-13

## 1) Decisao: adotar “modula” (monolito modular) e nao “hexagonal”

Escolha: **modula**.

Motivos (fit com o estado atual do repositorio):
- O ANATEJE ja esta organizado por “areas” e “modulos” na pratica (admin, associado, financeiro, auditoria, RBAC) e tem uma base de endpoints por modulo em `api/v1/*` e `api/financeiro/*`.
- A dor principal hoje nao e “falta de camadas puras”, e sim **divergencia e fragmentacao entre modulos** (contratos de resposta, CSRF, auditoria, legado financeiro). Modularizar reduz acoplamento por fronteiras de modulo sem exigir reescrever tudo em um corte unico.
- A migracao para “hexagonal/clean” costuma exigir uma reestruturacao transversal (todos os fluxos “passam” a seguir Domain/Application/Interface). Isso e bom, mas e um salto grande para um sistema que precisa seguir entregando e mantendo compatibilidade.
- O template “modula” foi desenhado para **migracao incremental**: da para mover 1 modulo por vez para dentro de `modules/*` e manter o host funcionando (inclusive convivendo com endpoints legados).

Trade-off aceito conscientemente:
- “modula” pede dependencias e contratos (PSR-7/15 + router) que o ANATEJE ainda nao usa. O plano abaixo controla isso por fases e por “entrada paralela” (sem quebrar o sistema atual).

## 2) Arquitetura alvo (como o ANATEJE vai ficar ao final)

### 2.1 Estrutura (alto nivel)
- **Host (app)**: fornece container, kernel HTTP, implementacoes de contratos e adaptadores para o mundo real (PDO, sessao, CSRF, auditoria, http client).
- **Modulos (packages)**: cada modulo vira um pacote Composer com:
  - `src/` (Application + Handlers HTTP + infraestrutura local do modulo)
  - `tests/` (unit/integration do modulo)
  - `Module.php` (registry/boot)
  - `composer.json` (dependencies e autoload do modulo)

### 2.2 Fronteiras de modulo propostas (versao inicial)

Admin:
- `admin-members` (associados)
- `admin-benefits` (beneficios)
- `admin-events` (eventos)
- `admin-posts` (comunicados)
- `admin-campaigns` (campanhas)
- `admin-permissions` (perfis/permissoes)
- `admin-integrations` (integracoes)
- `admin-audit` (auditoria UI/API)

Core:
- `auth` (login, reset, sessao)
- `rbac` (resolucao de permissoes + enforcement)
- `audit` (audit logger + query/export)
- `filters` (filtros salvos)
- `shared` (contratos e utilitarios transversais)

Financeiro:
- `finance-core` (contas_bancarias, pessoas, categorias_financeiras, centros_custos, lancamentos, pagamentos_parciais, transferencias como tipo semantico via lancamentos)
- `finance-legacy-adapters` (onde for inevitavel manter compatibilidade temporaria)
- `finance-domain-associativo` (adaptacao de “dominio escolar” para “associacao”: cobrancas/pagamentos/planos/contratos/mensalidades/rematricula → renovacao)

Publico/Associado (opcional na primeira onda):
- `public-site` (lead/contact)
- `member-area` (perfil, meus_eventos, meus_beneficios, comunicados)

### 2.3 Contratos minimos (o que o host fornece para os modulos)

Base (inspirado no `templates/modula/modules/contracts`):
- `RouteProvider` / `Route` (registrar rotas)
- `ResponseFactory`

Necessarios para o ANATEJE (propostos):
- `DbConnection` (fornece PDO ou wrapper)
- `AuthContextProvider` (resolve usuario atual e perfil)
- `PermissionChecker` (verifica `admin.x.y.action`)
- `CsrfValidator` (validar token em escrita)
- `AuditLogger` (grava em `audit_logs` com before/after/meta)
- `HttpClient` (integracoes externas via post json)
- `Clock` (tempo controlavel em testes)

Objetivo: o modulo nao sabe “de onde vem” a sessao, nem como o PDO e criado, nem como o CSRF e implementado. Ele recebe contrato.

## 3) Estrategia de migracao (sem parar o sistema)

### 3.1 Principio: coexistencia controlada por “entrada paralela”

Nao trocar o front controller atual de uma vez.

Criar uma entrada paralela para a arquitetura modular (exemplos possiveis):
- `/api/v2/*` atendido pelo kernel do host “modula”.
- (alternativa) `/modula/public/index.php` com roteamento dedicado e `.htaccess` apontando apenas esse prefixo.

Resultado:
- `api/v1/*` e `api/financeiro/*` continuam como estao durante a migracao.
- Cada modulo migrado ganha um endpoint equivalente em `api/v2/*`.
- Frontend pode migrar chamada por chamada (feature flag por URL base).

### 3.2 Regra de compatibilidade

Enquanto coexistir:
- Respostas em `api/v2` devem usar o mesmo envelope e semantica do sistema atual (`ok/data/meta/error`), para minimizar impacto no frontend.
- RBAC deve manter compatibilidade (permissao de pagina cobre permissao de acao quando aplicavel).
- Auditoria deve ser unica (tudo em `audit_logs`).

## 4) Workflow de TDD (como evitar regressao durante a refatoracao)

### 4.1 “Characterization tests” antes de mover qualquer coisa

Para cada endpoint a ser migrado:
- Criar testes de integracao que chamam o endpoint atual (v1/financeiro) e travam:
  - codigos HTTP
  - envelope de resposta
  - validacoes principais (422) e mensagens
  - regras de permissao (403)
  - escrita em auditoria quando aplicavel

Reaproveitar a infra ja existente:
- `tests/Support/ApiRunner.php`
- `tests/Support/run_endpoint.php`

### 4.2 Testes do kernel modular

Antes de migrar modulos reais:
- Teste funcional “dispatch” (como no `templates/modula/tests/Functional/RouteDispatchTest.php`):
  - roteamento funciona
  - middlewares basicos (auth, csrf, rbac) funcionam
  - response emitter funciona

### 4.3 Regra pratica

Nao mover codigo sem um teste que descreve o comportamento atual.

## 5) Plano por fases (detalhado e executavel)

### Fase 0 — Auditoria de contexto (audit-context-building) e preparacao

Entregas:
- Inventario de rotas:
  - `api/v1/*` (admin/associado/publico)
  - `api/financeiro/*`
- Mapa de dependencias tacitas (tacit knowledge):
  - o que usa `$_SESSION`
  - o que depende de `config/unidade_helper.php`
  - pontos sem CSRF
  - divergencias de auditoria (ex.: `auditoria_financeira`)
- Definicao do “contrato de resposta” oficial (o que e sucesso/erro).

Definition of Done:
- Lista de endpoints por modulo + prioridades.
- Lista de “regras que nao podem quebrar” (auth/rbac/auditoria).

### Fase 1 — Subir o host modular em paralelo (kernel + container)

Entregas:
- Criar o host modular dentro do ANATEJE usando a estrutura do template:
  - `src/Container/*`, `src/Http/Kernel.php`, `public/` (ou `api/v2/`), `config/modules.php`
- Instalar dependencias do template no `composer.json` do ANATEJE (router + PSR-7/15 libs) e manter phpunit.
- Primeiro modulo “Health/Ping” para validar a esteira.

Definition of Done:
- Um endpoint `GET /api/v2/health` (ou `ping`) respondendo no ambiente atual.
- `composer test` continua passando.

### Fase 2 — Contratos ANATEJE (shared) e adaptadores do host

Entregas:
- Criar pacote `modules/contracts` (ou estender o existente no template) com contratos ANATEJE.
- Implementar no host:
  - adaptador de DB (PDO)
  - adaptador de auth (sessao atual)
  - adaptador de rbac (reuso das permissoes atuais)
  - adaptador de auditoria (chama `anateje_audit_log`)
  - adaptador de csrf (se aplicavel)

Definition of Done:
- Modulos conseguem acessar DB/auth/rbac/audit via contratos, sem `require_once` em arquivos legados.

### Fase 3 — Migrar governanca primeiro (alto ROI, baixo risco)

Ordem sugerida:
1) `audit` (somente leitura/listagem/export, depois detalhe before/after/meta)
2) `permissions` (leitura primeiro; escrita depois)
3) `integrations` (com cuidado para nao vazar `api_key`)

Entregas:
- `api/v2/audit/*` com filtros e exportacao.
- `api/v2/permissions/*` com matrix e sync.
- `api/v2/integrations/*` com get/save/test.

Definition of Done:
- Auditoria completa e unica: tudo que for migrado ja grava em `audit_logs`.
- Endpoints “sensitivos” (permissions/integrations) passam a auditar create/update.

### Fase 4 — Migrar CRUDs admin (um por vez, com testes)

Para cada modulo (associados, beneficios, eventos, comunicados, campanhas):
1) Escrever/atualizar characterization tests do v1.
2) Criar modulo em `modules/<nome>/` com:
   - Application use-cases (list/get/save/delete/bulk/export)
   - Repositorios (PDO)
   - Handlers HTTP (PSR-15)
   - Validacao e erros padronizados
3) Criar testes do modulo (unit + integration).
4) Habilitar rota em `config/modules.php`.
5) Migrar frontend para chamar `api/v2` por feature flag.

Definition of Done por modulo:
- Paridade funcional (mesmos filtros/acoes/validacoes).
- RBAC por acao aplicado.
- Auditoria aplicada.
- Testes cobrindo happy path + validacoes + permissoes.

### Fase 5 — Financeiro: separar nucleo, matar legado, adaptar dominio

Subfase 5.1 (nucleo operacional):
- Migrar para `finance-core` os endpoints de maior reaproveitamento direto.
- Unificar auditoria (parar de usar `auditoria_financeira`).
- Normalizar contrato de resposta e seguranca (CSRF onde aplicavel).

Subfase 5.2 (compatibilidade/legado):
- Criar adaptadores que “traduzem” chamadas antigas para o nucleo (quando der).
- Depreciar rotas antigas com plano de desligamento.

Subfase 5.3 (dominio associativo):
- Especificar regras do negocio (renovacao, contribuicao, inadimplencia, status) e so depois codificar.
- Reescrever/renomear modulos acoplados ao escolar para equivalentes associativos.

Definition of Done:
- Fluxo financeiro MVP roda sem dependencia escolar obrigatoria.
- Auditoria e RBAC coerentes com o resto do sistema.

### Fase 6 — Desligamento gradual do legado (v1/financeiro) e limpeza

Entregas:
- Mapear quais endpoints v1 ainda sao usados.
- Migrar chamadas do frontend.
- Remover/arquivar apenas quando cobertura de testes garantir.

Definition of Done:
- `api/v2` se torna padrao.
- `api/v1` fica em modo compatibilidade minima ou e removido por etapa planejada.

## 6) Riscos e mitigacoes

- Risco: inflar dependencias no host.
  - Mitigacao: adotar dependencias do template apenas no “host modular” e nao tocar no fluxo legado.
- Risco: sessao e globals criarem acoplamento invisivel.
  - Mitigacao: contratos (`AuthContextProvider`, `PermissionChecker`) e testes de permissao por endpoint.
- Risco: divergencia de envelope de resposta quebrar frontend.
  - Mitigacao: padronizar envelope e manter compatibilidade em `api/v2`.

## 7) Primeiros modulos recomendados (para iniciar com seguranca)

1) `audit` (governanca, leitura, baixo risco)
2) `integrations` (pequeno, sensivel, bom para travar boas praticas)
3) `permissions` (governanca e base de RBAC)

Depois:
- `members` (associados) porque e o coracao do dominio e ja tem muitas validacoes no v1.
