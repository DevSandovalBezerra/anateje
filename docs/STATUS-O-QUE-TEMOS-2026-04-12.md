# STATUS DO SISTEMA (ANATEJE) — O QUE TEMOS

Data da analise: 2026-04-12
Fonte principal: arquivos em `docs/` (PLANO + BACKLOG + UPDATES + auditorias tecnicas)

## 1) Em que fase este sistema esta (planejamento x execucao)

- Planejamento: existe e esta bem detalhado (backlog por epico/onda + plano de migracao do financeiro).
- Execucao: a maior parte do MVP e do “core operacional” ja foi implementada; o sistema entrou na fase de consolidacao (governanca, hardening de seguranca, cobertura de auditoria, testes e adaptacao de dominio do financeiro).

Leitura por ondas (conforme `BACKLOG-IMPLEMENTACAO-02-03-2026.md`):
- Onda 0 (Fundacao): implementada em grande parte (contrato API, componentes base, navegacao/menus).
- Onda 1 (Core operacional): implementada (CRUDs principais admin e area do associado).
- Onda 2 (Escala operacional): implementada em grande parte (acoes em lote, filtros salvos, importacao CSV, auditoria).
- Onda 3 (Governanca e inteligencia): parcialmente implementada (RBAC fino e base de dashboard por periodo existem; hardening completo e relatorios consolidados seguem como pendencia).

Leitura por fases do financeiro (conforme `PLANO-MIGRACAO-FINANCEIRO-03-03-2026.md`):
- Fase 1 (compatibilidade estrutural minima): executada (rotas/menus/titulos/permissoes e helper de unidade presentes).
- Fase 2/3 (seguranca/contrato API + UI no padrao): parcialmente executadas (ha unificacao de fluxo e pontos de compatibilidade; ainda existem partes legadas e divergencias).
- Fase 4 (nucleo operacional): parcialmente pronto (varios endpoints ja existem e estao em uso).
- Fase 5 (adaptacao de dominio escolar → associacao): ainda aberta (itens com acoplamento escolar seguem como risco/pendencia).

## 2) Produto entregue (MVP funcional)

### 2.1 Site publico (institucional)
- Paginas publicas: home + sobre + beneficios + eventos + blog + contato + filiacao.
- Layout publico compartilhado.
- Endpoint publico para lead/contato.

Referencia: `UPDATE-02-03-2026.md`.

### 2.2 Autenticacao e seguranca base
- Login com rate limit.
- Recuperacao de senha (solicitacao, validacao de token, redefinicao).
- Tabelas de suporte criadas automaticamente (login attempts + tokens).

Referencia: `UPDATE-02-03-2026.md`.

### 2.3 Admin (operacao interna)
- CRUDs principais implementados:
  - Associados (com validacoes fortes, historico de status, exportacao CSV, importacao CSV com preview/commit, bulk status).
  - Beneficios (CRUD + elegibilidade + bulk status + exportacao CSV + vinculo em lote por filtros).
  - Eventos (CRUD + inscricoes + fila de espera + check-in + bulk status + exportacao).
  - Comunicados (CRUD + segmentacao + agendamento + bulk status + exportacao CSV).
  - Campanhas (CRUD + preview de publico + logs + bulk status).
- Pastas de associados (operacoes de pasta/arquivo, incluindo upload/download) com auditoria.
- Auditoria (API + tela admin) com filtros, paginacao e exportacao CSV.
- Filtros salvos por usuario/modulo (API + UI em modulos principais).
- Toolbar padronizada nos CRUDs principais.

Referencia: `UPDATE-02-03-2026.md` e `AUDITORIA-FLUXO-E-COBERTURA-CRUDS-04-03-2026.md`.

### 2.4 RBAC (permissoes) e enforcement em runtime
- Catalogo de permissoes por pagina e por acao.
- Enforcement na API por `anateje_require_permission(...)` (por acao).
- Enforcement na UI via `window.anatejePerms.can(code)` (oculta/bloqueia botoes/acoes).
- Sidebar e roteamento calculados por permissoes efetivas.

Referencia: `UPDATE-02-03-2026.md`.

### 2.5 Design system e coerencia visual
- Documento de regras visuais (`system.design.md`) com tokens/temas.
- Ajustes no tema claro e consistencia do admin (sidebar/contraste/tokens).

Referencia: `docs/system.design.md` e `UPDATE-02-03-2026.md`.

### 2.6 Financeiro (integrado em parte)
- Modulo financeiro existe em `api/financeiro/*`, `frontend/financeiro/*`, `frontend/js/financeiro/*`.
- Fluxo “contas x lancamentos” unificado com alias para manter compatibilidade.
- Auditoria no financeiro: boa cobertura em varios endpoints via `financeiro_audit(...)`, mas com excecoes legadas.

Referencia: `PLANO-MIGRACAO-FINANCEIRO-03-03-2026.md`, `UPDATE-03-03-2026.md` e `AUDITORIA-FLUXO-E-COBERTURA-CRUDS-04-03-2026.md`.

### 2.7 Testes automatizados (base)
- PHPUnit instalado e executando com suite inicial (unit + integration).
- Runner para endpoints que encerram com `exit`.

Referencia: `UPDATE-05-03-2026.md`.

## 3) Observacoes arquiteturais (estado do sistema)

Pontos negentropicos (crescimento, valor composto):
- RBAC por acao aplicado em API e UI (reduz risco operacional e facilita evolucao).
- Auditoria central (`audit_logs`) e tela de consulta/exportacao (base forte de governanca).
- Backlog e updates bem escritos: conhecimento explicito e rastreavel no proprio repositorio.
- Testes automatizados iniciais: base para evoluir com menos regressao.

Pontos entropicos (decadencia/complexidade sem valor):
- Financeiro ainda mistura trilhas e padroes (parte moderna + parte legada), gerando divergencia de auditoria/seguranca/contratos.
- Ha lacunas de auditoria em modulos sensiveis (ex.: permissoes e integracoes) e em alguns CRUDs financeiros.
- Adaptacao de dominio (escolar → associativo) ainda e uma fronteira grande e carregada de “conhecimento tacito” (o que permanece, o que some, e quais regras de negocio mudam).
