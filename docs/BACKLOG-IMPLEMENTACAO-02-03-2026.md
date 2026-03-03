# BACKLOG DE IMPLEMENTACAO - PRD + KIT MVP

Data: 2026-03-02
Horizonte: 90 dias
Status: Pronto para execucao tecnica

## 1. Diretrizes do backlog
- Objetivo: transformar o plano funcional em entregas executaveis no codigo.
- Regra de escopo: integracoes externas (Mailchimp/WhatsApp) continuam fora, aguardando decisao.
- Estrutura: Epicos -> Historias -> Tarefas tecnicas.
- Escala de estimativa: SP (story points) em escala 1, 2, 3, 5, 8, 13.

## 2. Definicao de pronto (DoD)
- API com validacao server-side e respostas padronizadas.
- Tela admin com estados de loading, vazio, erro e sucesso.
- Permissao RBAC aplicada na API e na UI.
- Log minimo de alteracoes sensiveis.
- Teste funcional manual registrado no update da sprint.

## 3. Roadmap por onda

### Onda 0 (Semana 1) - Fundacao
1. E00 - Contrato padrao de CRUD/API
2. E01 - Componentes base de tela (lista + formulario)
3. E02 - Mapa de menus admin (nova arquitetura de navegacao)

### Onda 1 (Semanas 2-4) - Core operacional
1. E10 - Associados CRUD completo
2. E11 - Beneficios consolidado
3. E12 - Eventos com inscritos/exportacao
4. E13 - Comunicados com segmentacao basica

### Onda 2 (Semanas 5-7) - Escala operacional
1. E20 - Campanhas internas
2. E21 - Auditoria por modulo
3. E22 - Filtros avancados e acoes em lote
4. E23 - Importacao CSV de associados

### Onda 3 (Semanas 8-9) - Governanca e inteligencia
1. E30 - Relatorios consolidados
2. E31 - RBAC fino (acao por modulo)
3. E32 - Hardening de seguranca (CSRF/sessao/limites)

## 4. Backlog por epico

## E00 - Contrato padrao de CRUD/API (SP: 13)
Historias:
1. US-0001 Definir padrao unico para `list`, `save`, `delete`, `export`.
2. US-0002 Padronizar envelope de erro e metadados de paginacao.
3. US-0003 Centralizar validacoes comuns e utilitarios.

Tarefas tecnicas:
- TSK-0001 Criar padrao de query params (`page`, `per_page`, `q`, `sort`, `order`, `filters`).
- TSK-0002 Definir resposta JSON padrao (`ok`, `data`, `meta`, `error`).
- TSK-0003 Extrair helpers em `api/v1/_bootstrap.php` para reuso.
- TSK-0004 Adequar endpoints atuais (`members`, `benefits`, `events`, `posts`, `campaigns`, `permissions`).

Dependencias: nenhuma
Saida esperada: contrato API unificado para todos os modulos.

## E01 - Componentes base de tela (SP: 8)
Historias:
1. US-0101 Criar padrao visual de tabela admin.
2. US-0102 Criar padrao visual de formulario admin.
3. US-0103 Criar padrao de notificacao/confirmacao de acoes.

Tarefas tecnicas:
- TSK-0101 Consolidar classes utilitarias em `assets/css/main.css`.
- TSK-0102 Criar partial reutilizavel para toolbar de lista.
- TSK-0103 Padronizar modal de confirmacao de exclusao.
- TSK-0104 Padronizar feedback de erro de validacao por campo.

Dependencias: E00
Saida esperada: UX coerente em todos os CRUDs.

## E02 - Menus e navegacao admin (SP: 8)
Historias:
1. US-0201 Reestruturar menu para: Dashboard, Associados, Conteudo, Relacionamento, Configuracoes, Governanca.
2. US-0202 Aplicar visibilidade por permissao em submenu.
3. US-0203 Incluir placeholders de modulos futuros sem quebrar navegacao.

Tarefas tecnicas:
- TSK-0201 Refatorar estrutura em `includes/sidebar.php`.
- TSK-0202 Ajustar metadados e rotas em `includes/page_router.php`.
- TSK-0203 Atualizar rotas existentes para novos grupos.
- TSK-0204 Validar comportamento mobile e colapso de sidebar.

Dependencias: E01
Saida esperada: arquitetura de menu pronta para crescimento.

## E10 - Associados CRUD completo (SP: 21)
Historias:
1. US-1001 Como admin, quero cadastrar associado com todos os campos do PRD.
2. US-1002 Como admin, quero editar e ativar/inativar associado.
3. US-1003 Como admin, quero filtrar e exportar associados.
4. US-1004 Como admin, quero validacao de CPF/email/matricula unicos.

Tarefas tecnicas:
- TSK-1001 Revisar schema e constraints da tabela de membros.
- TSK-1002 Implementar `admin_get` e `admin_save` robusto em `api/v1/members.php`.
- TSK-1003 Implementar `admin_toggle_status` e `admin_export`.
- TSK-1004 Implementar formulario completo em `frontend/admin/associados.php`.
- TSK-1005 Implementar mascaras e validacao cliente (CPF/telefone/CEP).
- TSK-1006 Integrar busca CEP e fallback manual.
- TSK-1007 Testar cenarios de duplicidade e mensagens de erro.

Dependencias: E00, E01
Saida esperada: modulo associados operacional de ponta a ponta.

## E11 - Beneficios consolidado (SP: 13)
Historias:
1. US-1101 Como admin, quero CRUD completo de beneficios.
2. US-1102 Como admin, quero ordenar exibicao e ativar/inativar beneficio.
3. US-1103 Como admin, quero vincular beneficios em lote para associados.

Tarefas tecnicas:
- TSK-1101 Revisar `api/v1/benefits.php` para contrato padrao.
- TSK-1102 Incluir filtros e exportacao no admin.
- TSK-1103 Criar acao em lote de vinculacao por filtros.
- TSK-1104 Ajustar `frontend/admin/beneficios.php` para novo fluxo.

Dependencias: E00, E01, E10
Saida esperada: catalogo e operacao de beneficios consistentes.

## E12 - Eventos com inscritos/exportacao (SP: 13)
Historias:
1. US-1201 Como admin, quero CRUD de evento com status de publicacao.
2. US-1202 Como admin, quero listar inscritos e exportar CSV.
3. US-1203 Como admin, quero encerrar evento e bloquear novas inscricoes.

Tarefas tecnicas:
- TSK-1201 Revisar `api/v1/events.php` com status `draft/published/closed`.
- TSK-1202 Criar endpoint de exportacao de inscritos.
- TSK-1203 Ajustar tela `frontend/admin/eventos.php`.
- TSK-1204 Criar indicadores de vagas usadas/disponiveis.

Dependencias: E00, E01
Saida esperada: operacao de eventos pronta para uso admin diario.

## E13 - Comunicados com segmentacao basica (SP: 13)
Historias:
1. US-1301 Como admin, quero publicar comunicado interno.
2. US-1302 Como admin, quero segmentar por categoria, status, UF e lotacao.
3. US-1303 Como admin, quero arquivar e consultar historico.

Tarefas tecnicas:
- TSK-1301 Evoluir `api/v1/posts.php` para filtros de audiencia.
- TSK-1302 Ajustar `frontend/admin/comunicados.php` com construtor de filtros.
- TSK-1303 Exibir metrica interna de audiencia estimada.
- TSK-1304 Ajustar leitura no painel do associado.

Dependencias: E00, E10
Saida esperada: comunicacao interna segmentada funcional.

## E20 - Campanhas internas (SP: 8)
Historias:
1. US-2001 Como admin, quero criar campanhas sem disparo externo.
2. US-2002 Como admin, quero salvar audiencia e agendamento interno.

Tarefas tecnicas:
- TSK-2001 Evoluir `api/v1/campaigns.php` para modelo interno.
- TSK-2002 Ajustar `frontend/admin/campanhas.php`.
- TSK-2003 Criar status de execucao interna e log basico.

Dependencias: E13
Saida esperada: modulo de campanhas operando em modo interno.

## E21 - Auditoria por modulo (SP: 8)
Historias:
1. US-2101 Como gestor, quero ver quem alterou registros sensiveis.
2. US-2102 Como gestor, quero filtrar auditoria por modulo e periodo.

Tarefas tecnicas:
- TSK-2101 Criar tabela `audit_logs` (ou adequar existente).
- TSK-2102 Registrar eventos de create/update/delete/status.
- TSK-2103 Criar API `api/v1/audit.php` (listagem com filtro).
- TSK-2104 Criar tela admin de auditoria.

Dependencias: E00
Saida esperada: rastreabilidade operacional minima.

## E22 - Filtros avancados e acoes em lote (SP: 8)
Historias:
1. US-2201 Como admin, quero aplicar filtros compostos salvos.
2. US-2202 Como admin, quero executar acoes em lote com confirmacao.

Tarefas tecnicas:
- TSK-2201 Implementar serializacao de filtros por modulo.
- TSK-2202 Implementar selecao multipla em tabelas.
- TSK-2203 Implementar acoes em lote (ativar/inativar/arquivar).

Dependencias: E10, E11, E13
Saida esperada: ganho de produtividade operacional.

## E23 - Importacao CSV de associados (SP: 13)
Historias:
1. US-2301 Como admin, quero importar associados via CSV.
2. US-2302 Como admin, quero preview de erros antes de confirmar.
3. US-2303 Como admin, quero relatorio final de importacao.

Tarefas tecnicas:
- TSK-2301 Criar parser CSV com validacao de colunas.
- TSK-2302 Criar modo dry-run para preview.
- TSK-2303 Criar commit transacional por lote.
- TSK-2304 Criar UI de upload + feedback detalhado.

Dependencias: E10
Saida esperada: onboarding em massa de associados.

## E30 - Relatorios consolidados (SP: 8)
Historias:
1. US-3001 Como gestor, quero KPI de associados, eventos e comunicados.
2. US-3002 Como gestor, quero exportar relatorios por periodo.

Tarefas tecnicas:
- TSK-3001 Evoluir `api/v1/dashboard.php` com filtros de periodo.
- TSK-3002 Criar endpoints de exportacao por modulo.
- TSK-3003 Ajustar `frontend/dashboard/admin.php` com blocos de KPI.

Dependencias: E10, E12, E13, E21
Saida esperada: visao gerencial consolidada.

## E31 - RBAC fino (SP: 8)
Historias:
1. US-3101 Como admin master, quero permissao por acao (view/create/edit/delete/export).
2. US-3102 Como admin master, quero limitar modulos sensiveis por perfil.

Tarefas tecnicas:
- TSK-3101 Evoluir `api/v1/permissions.php` para permissoes por acao.
- TSK-3102 Ajustar verificacoes em `includes/rbac.php`.
- TSK-3103 Aplicar bloqueios de botao/acao na UI.

Dependencias: E21
Saida esperada: governanca de acesso refinada.

## E32 - Hardening de seguranca (SP: 8)
Historias:
1. US-3201 Como sistema, quero CSRF consistente em formularios sensiveis.
2. US-3202 Como sistema, quero politicas de sessao e rate limit reforcadas.
3. US-3203 Como sistema, quero trilha de erros de seguranca.

Tarefas tecnicas:
- TSK-3201 Implementar token CSRF global para rotas de escrita.
- TSK-3202 Revisar expiracao de sessao e renovacao segura.
- TSK-3203 Revisar limites por IP/usuario em rotas criticas.
- TSK-3204 Revisar mensagens de erro para nao vazar detalhes internos.

Dependencias: E00
Saida esperada: baseline de seguranca para crescimento.

## 5. Priorizacao objetiva (ordem de execucao)
1. E00
2. E01
3. E02
4. E10
5. E11
6. E12
7. E13
8. E21
9. E31
10. E22
11. E20
12. E23
13. E30
14. E32

## 6. Backlog de menus e funcionalidades (produto)

Melhorias de menu:
1. Separar "Conteudo" de "Relacionamento" para reduzir mistura de contexto.
2. Mover "Permissoes" e "Usuarios" para "Configuracoes".
3. Criar area "Governanca" com auditoria e relatorios.
4. Manter "Integracoes" visivel como placeholder de roadmap.

Melhorias de funcionalidades:
1. Segmentos salvos reutilizaveis em comunicados/campanhas.
2. Acao em lote padrao em todos os CRUDs.
3. Exportacao CSV padrao em todos os modulos administrativos.
4. Relatorios com filtro temporal e por categoria/status.
5. Auditoria navegavel por modulo, usuario e periodo.

## 7. Quadro inicial de sprint (sugestao)

Sprint 1:
- E00, E01, E02

Sprint 2:
- E10 (50%), E11 (50%)

Sprint 3:
- E10 (50%), E11 (50%), E12 (50%)

Sprint 4:
- E12 (50%), E13 (100%)

Sprint 5:
- E21, E31

Sprint 6:
- E22, E20, E23 (50%)

Sprint 7:
- E23 (50%), E30, E32

## 8. Riscos e mitigacao
- Risco: inconsistencias de schema legado.
Mitigacao: migracoes incrementais e scripts idempotentes por modulo.

- Risco: divergencia entre RBAC de UI e API.
Mitigacao: bloquear sempre na API e espelhar na UI apenas para UX.

- Risco: regressao visual ao padronizar formularios.
Mitigacao: adotar componentes base e revisar telas por checklist.

- Risco: acoplamento prematuro com integracoes.
Mitigacao: manter camada de adaptador e placeholder interno ate decisao.

## 9. Proximo passo imediato
Executar Sprint 1, iniciando por E00:
1. formalizar contrato CRUD
2. aplicar contrato em `members`, `benefits`, `events`, `posts`
3. publicar update tecnico da sprint em `docs/UPDATE-<data>.md`
