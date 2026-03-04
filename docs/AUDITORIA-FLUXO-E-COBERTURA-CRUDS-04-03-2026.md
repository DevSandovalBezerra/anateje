# Auditoria - Fluxo e Cobertura nos CRUDs

Data de referencia: 2026-03-04
Escopo analisado: `api/v1/*`, `api/financeiro/*`, `frontend/admin/auditoria.php`

## 1) Visao geral do funcionamento

A auditoria do sistema hoje esta concentrada em dois caminhos:

1. Caminho principal (unificado): tabela `audit_logs`
- Escrita via funcao `anateje_audit_log(...)` (`api/v1/_bootstrap.php`)
- Modulos admin (`api/v1/*`) gravam direto nessa funcao
- Modulos financeiro modernos (`api/financeiro/*`) gravam via wrapper `financeiro_audit(...)`, que delega para `anateje_audit_log(...)`

2. Caminho legado financeiro: tabela `auditoria_financeira`
- Usado por `api/financeiro/transferencias.php` (metodo `registrarAuditoria(...)`)
- Consultado por `api/financeiro/auditoria.php`

Resultado: existe auditoria funcional, mas com duas trilhas de armazenamento.

## 2) Fluxo ponta a ponta (caminho principal)

1. Requisicao autenticada entra no endpoint de negocio.
2. Endpoint valida permissao e executa operacao de negocio (create/update/delete/cancel/etc).
3. Endpoint monta contexto de auditoria:
- `user_id`
- `modulo`
- `acao`
- `entidade` e `entidade_id`
- `before` e `after` (quando aplicavel)
- `meta` (quando aplicavel)
4. `anateje_audit_log(...)` serializa `before/after/meta` em JSON e grava em `audit_logs`.
5. Tela admin consulta em `api/v1/audit.php?action=admin_list` com filtros e paginacao.
6. Exportacao CSV usa `api/v1/audit.php?action=admin_export_csv` (limite de 10.000 linhas).

## 3) Estrutura persistida em `audit_logs`

Campos gravados:
- `user_id`
- `modulo`
- `acao`
- `entidade`
- `entidade_id`
- `antes_json`
- `depois_json`
- `meta_json`
- `ip`
- `user_agent`
- `created_at`

Indices:
- `(modulo, acao)`
- `(entidade, entidade_id)`
- `(user_id)`
- `(created_at)`

## 4) Cobertura de auditoria nos CRUDs - Financeiro (`api/financeiro`)

Legenda:
- `OK`: operacoes de escrita auditadas
- `PARCIAL`: parte do ciclo de vida auditado
- `NAO`: CRUD sem chamada de auditoria
- `LEGADO`: grava em `auditoria_financeira`, nao em `audit_logs`

| Modulo | CRUD base | Cobertura | Observacoes |
|---|---|---|---|
| `categorias_financeiras.php` | listar/obter/criar/atualizar/excluir | OK | `financeiro_audit` em criar/atualizar/excluir |
| `centros_custos.php` | listar/obter/criar/atualizar/excluir | OK | `financeiro_audit` em criar/atualizar/excluir |
| `contas_bancarias.php` | listar/obter/criar/atualizar/excluir | OK | `financeiro_audit` em criar/atualizar/excluir |
| `pessoas.php` | listar/obter/criar/atualizar/excluir | OK | `financeiro_audit` em criar/atualizar/excluir |
| `planos.php` | listar/obter/criar/atualizar/excluir | OK | Tambem audita `clonar` |
| `pagamentos_parciais.php` | listar/obter/criar/atualizar/excluir | OK | `financeiro_audit` em criar/atualizar/excluir |
| `lancamentos.php` | listar/obter/criar/atualizar/excluir | OK | Tambem audita `duplicar`, `acoes_massa`, `quitar` |
| `contratos.php` | listar/obter/criar/atualizar/excluir | OK | Tambem audita `cancelar` e `renovar` |
| `cobrancas.php` | listar/obter/criar/atualizar/(cancelar) | PARCIAL | Nao ha `excluir`; audita `criar/atualizar/cancelar` + lote/notificacao |
| `pagamentos.php` | listar/obter/(registrar)/atualizar/(cancelar) | PARCIAL | Nao ha `excluir`; audita `registrar/atualizar/cancelar` |
| `transferencias.php` | listar/obter/criar/excluir | LEGADO | Audita em `auditoria_financeira` (nao `audit_logs`) |
| `orcamentos.php` | listar/obter/criar/atualizar/excluir | NAO | Sem chamada de auditoria |
| `receitas_despesas.php` | listar/obter/criar/atualizar/excluir | NAO | Sem chamada de auditoria |
| `contas_financeiras.php` | listar/obter/criar/atualizar/excluir | NAO | Sem chamada de auditoria |
| `contas.php` | listar/obter/criar/excluir | NAO | Sem chamada de auditoria |

## 5) Cobertura de auditoria nos CRUDs - Admin (`api/v1`)

| Modulo | CRUD base | Cobertura | Observacoes |
|---|---|---|---|
| `members.php` (`admin.associados`) | list/get/save/delete | OK | Audita create/update/delete + status, bulk status, import CSV |
| `benefits.php` (`admin.beneficios`) | admin_list/admin_save/admin_delete | OK | Audita create/update/delete + bulk status + vinculo em lote |
| `events.php` (`admin.eventos`) | admin_list/admin_save/admin_delete | OK | Audita create/update/delete + checkin/checkout/fila |
| `posts.php` (`admin.comunicados`) | admin_list/admin_save/admin_delete | OK | Audita create/update/delete + bulk status |
| `campaigns.php` (`admin.campanhas`) | admin_list/admin_save/admin_delete | OK | Audita create/update/delete + bulk status + run |
| `member_folders.php` (`admin.pastas_associados`) | create/rename/delete/move/upload | OK | Audita operacoes de pasta e arquivo |
| `permissions.php` (`admin.permissoes`) | profile save/delete/permissions save | NAO | Sem `anateje_audit_log` |
| `integrations.php` (`admin.integracoes`) | save/test | NAO | Sem `anateje_audit_log` |

## 6) Consulta e exportacao da auditoria (admin)

Arquivo: `api/v1/audit.php`

- `admin_list`
  - Requer permissao `admin.auditoria.view`
  - Filtros: `module`, `operation`, `entity`, `user_id`, `date_from`, `date_to`, `q`
  - Paginacao: default 30, max 200
- `admin_export_csv`
  - Requer permissao `admin.auditoria.export`
  - Limite fixo: 10.000 linhas

Arquivo: `frontend/admin/auditoria.php`
- Tela com filtros, paginacao, salvamento de filtros e exportacao CSV
- Mostra colunas: data, usuario, modulo, acao, entidade, entidade_id, ip

## 7) Pontos criticos identificados

1. Fragmentacao de trilha de auditoria
- Parte do financeiro (transferencias) grava em `auditoria_financeira`.
- Resto do sistema grava em `audit_logs`.
- Impacto: consulta central (`admin/auditoria`) nao enxerga tudo.

2. Tabela legada sem garantia de schema no codigo
- Foi encontrada referencia a `auditoria_financeira`, mas sem `CREATE TABLE IF NOT EXISTS` no codigo analisado.
- Impacto: risco de falha silenciosa na trilha legada, dependendo do banco.

3. Lacunas de cobertura em CRUDs relevantes
- Sem auditoria em: `orcamentos`, `receitas_despesas`, `contas_financeiras`, `contas`.
- Sem auditoria em admin sensivel: `permissions` e `integrations`.

4. Falha de auditoria nao bloqueia transacao de negocio
- `anateje_audit_log(...)` captura excecao internamente e apenas escreve em log de erro.
- Impacto: operacao pode concluir com sucesso sem trilha de auditoria.

5. Detalhe de diff gravado, mas nao exposto na consulta admin
- `antes_json`, `depois_json`, `meta_json` sao persistidos.
- `api/v1/audit.php` e tela admin atual nao retornam/exibem esses campos.
- Impacto: baixa rastreabilidade investigativa na UI padrao.

6. Dependencia de `$_SESSION['user_id']` em partes legadas
- Em fluxos legados financeiros, se usuario nao vier em sessao, pode nao haver log.
- Impacto: registros sem ator ou ausencia de registro.

## 8) Recomendacao tecnica (ordem sugerida)

1. Unificar tudo em `audit_logs`
- Migrar `transferencias.php` para `financeiro_audit(...)`.
- Depreciar gradualmente `auditoria_financeira` e `api/financeiro/auditoria.php`.

2. Fechar gaps de cobertura nos CRUDs sem auditoria
- Prioridade alta: `permissions.php`, `integrations.php`, `orcamentos.php`, `receitas_despesas.php`, `contas_financeiras.php`, `contas.php`.

3. Expor `antes_json`, `depois_json`, `meta_json` no endpoint/admin
- Adicionar modo detalhado em `api/v1/audit.php`.
- Exibir detalhe sob demanda (drawer/modal) na tela `frontend/admin/auditoria.php`.

4. Definir politica de resiliencia da auditoria
- Decidir por modulo se "falha de auditoria" deve bloquear a escrita de negocio (modo estrito) ou nao (modo resiliente).

5. Padronizar ator (`user_id`) no financeiro legado
- Priorizar `auth['sub']` em vez de depender de sessao em APIs modernizadas.

## 9) Conclusao objetiva

O recurso de auditoria existe e esta bem implementado no nucleo (`audit_logs` + filtros + exportacao), com boa cobertura em varios CRUDs. Porem, a cobertura ainda e heterogenea: ha modulos sem auditoria e uma trilha legada paralela (`auditoria_financeira`) que reduz consistencia e visibilidade central.
