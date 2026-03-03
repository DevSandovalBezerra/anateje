# PLANO ESTRUTURADO - CRUD ADMIN + EVOLUCAO FUNCIONAL

Data: 2026-03-02
Status: Guia de referencia para futuras implementacoes

## 1. Objetivo
Definir um plano pratico e escalavel para:
- concluir os CRUDs da area administrativa
- organizar menus e navegacao
- priorizar melhorias de produto alinhadas ao ecossistema de associacoes
- orientar execucao em fases, sem travar o projeto por integracoes externas

## 2. Premissas e limites
- Base atual: MVP publico + autenticacao + admin basico + permissoes em andamento.
- Item 3 (integracoes externas: Mailchimp/WhatsApp) permanece adiado por decisao de negocio.
- Foco imediato: consolidar operacao interna (dados, fluxos, UX, governanca).

## 3. Meta de arquitetura funcional (admin)
Padrao recomendado para todos os modulos:
- Lista: filtros, busca, ordenacao, paginacao, exportacao CSV.
- Formulario: create/edit em tela dedicada ou modal padrao.
- Acao em lote: ativar, inativar, arquivar, excluir (quando permitido).
- Historico: trilha minima de auditoria por registro (quem, quando, o que mudou).
- Permissao: controle por papel (RBAC) para visualizar, criar, editar, excluir, exportar.

## 4. Mapa de menus recomendado

### 4.1 Menu Admin (principal)
1. Dashboard
2. Associados
3. Conteudo
4. Relacionamento
5. Configuracoes
6. Governanca

### 4.2 Submenus sugeridos
1. Dashboard
- Visao geral
- KPIs operacionais

2. Associados
- Cadastro de associados
- Categorias e planos
- Beneficios por associado
- Renovacoes e status

3. Conteudo
- Beneficios (catalogo)
- Eventos
- Comunicados
- Blog/Noticias (se habilitado)

4. Relacionamento
- Campanhas
- Segmentos/audiencias
- Fila de envios internos

5. Configuracoes
- Usuarios admin
- Perfis e permissoes
- Parametros gerais (UF, lotacao, categorias, templates)
- Integracoes (placeholder ate decisao)

6. Governanca
- Auditoria
- Relatorios
- Exportacoes

## 5. Plano de CRUD por modulo (guia de implementacao)

### 5.1 Associados (prioridade alta)
Objetivo: sair de listagem basica para CRUD completo com ciclo de vida.

Entregas:
- Form create/edit com campos do PRD.
- Validacoes: CPF unico, email funcional unico, telefone, categoria obrigatoria, status.
- Acoes: ativar/inativar, renovar, alterar categoria, anexar observacao interna.
- Importacao CSV (fase 2) com validacao e preview.
- Exportacao CSV com colunas padrao.

Campos minimos:
- nome, cpf, matricula, email_funcional, telefone
- cargo, lotacao, data_filiacao
- categoria (PARCIAL/INTEGRAL), status (ativo/inativo)
- contribuicao_mensal, cep, logradouro, numero, bairro, cidade, uf

### 5.2 Beneficios (prioridade alta)
Objetivo: consolidar catalogo + atribuicao por associado.

Entregas:
- CRUD completo com status e ordenacao.
- Regras de elegibilidade (por categoria/status) em fase 2.
- Vinculo rapido com associados (acao em lote).

Campos minimos:
- nome, descricao, parceiro, link, status, ordem_exibicao
- regra_categoria (opcional), regra_status (opcional)

### 5.3 Eventos (prioridade alta)
Objetivo: fluxo completo de publicacao e inscricoes.

Entregas:
- CRUD com agenda, capacidade, status (rascunho/publicado/encerrado).
- Inscricoes: lista de inscritos, cancelamento, confirmacao de presenca.
- Exportacao de inscritos (CSV).

Campos minimos:
- titulo, descricao, tipo (presencial/online/hibrido)
- local_ou_link, data_inicio, data_fim
- vagas_totais, status, imagem_capa

### 5.4 Comunicados (prioridade alta)
Objetivo: padronizar comunicacao in-app com segmentacao interna.

Entregas:
- CRUD com status (rascunho/publicado/arquivado).
- Segmentacao por categoria, status, UF, lotacao.
- Agendamento interno (fase 2).

Campos minimos:
- titulo, resumo, conteudo, prioridade
- publico_alvo (filtros), status, data_publicacao

### 5.5 Campanhas (prioridade media)
Objetivo: organizar envios e historico mesmo sem provedores externos.

Entregas:
- CRUD de campanha com publico alvo salvo.
- Simulacao de audiencia estimada.
- Log interno de execucao (sem disparo externo nesta fase).

Campos minimos:
- nome, canal (interno/email/whatsapp placeholder), objetivo
- filtros_segmentacao, status, data_programada

### 5.6 Permissoes e usuarios admin (prioridade alta)
Objetivo: governanca operacional e seguranca.

Entregas:
- CRUD de perfis e usuarios.
- Matriz de permissoes por modulo e acao.
- Restricao de acoes sensiveis (excluir, exportar, gerenciar usuarios).

## 6. Padrao unico de formularios (design + UX)
Aplicar o mesmo padrao em todos os CRUDs:
- Header com titulo, descricao, status do registro.
- Blocos: dados principais, regras, publicacao/visibilidade, auditoria.
- Rodape fixo com acoes: Cancelar, Salvar rascunho, Publicar/Salvar.
- Validacao inline por campo e sumario de erros no topo.
- Estados de tela: loading, vazio, erro, sucesso.
- Confirmacao obrigatoria para exclusao e mudancas criticas.

## 7. Melhorias sugeridas pelo benchmark (futuro)

### 7.1 Autoatendimento do associado
- Renovacao guiada no perfil.
- Alteracao de dados e preferencias de comunicacao.
- Historico de pagamentos/faturas (quando financeiro entrar).

### 7.2 Operacao de eventos madura
- Formularios de inscricao customizaveis por evento.
- Lotes/tipos de ingresso (membro x nao membro).
- Lista de espera e check-in.

### 7.3 Segmentacao e engajamento
- Segmentos salvos (ex.: ativos integral AM).
- Regras de recorrencia para comunicados/campanhas.
- Medicao de alcance interno por segmento.

### 7.4 Governanca e dados
- Auditoria navegavel por modulo.
- Relatorios recorrentes (renovacao, evasao, engajamento em eventos).
- Regras de acesso por perfil com menor privilegio.

## 8. Roadmap recomendado (90 dias)

### Fase 0 - Fundacao (1 semana)
- Definir contrato unico de API CRUD (list/save/delete/export).
- Padronizar componentes de formulario e tabela.
- Fechar dicionario de campos por modulo.

### Fase 1 - Core operacional (3 semanas)
- Associados CRUD completo.
- Beneficios CRUD + vinculo por associado.
- Eventos CRUD + inscritos/exportacao.
- Comunicados CRUD com segmentacao basica.

### Fase 2 - Escala operacional (3 semanas)
- Campanhas internas com publico salvo.
- Auditoria por modulo.
- Filtros avancados e acoes em lote.
- Importacao CSV de associados.

### Fase 3 - Inteligencia e governanca (2 semanas)
- Relatorios consolidados (dashboard + exportacoes).
- Ajustes de RBAC fino.
- Hardening de seguranca (CSRF global, politicas de sessao, limites operacionais).

Integracoes externas:
- Mantidas em trilha paralela de descoberta e decisao de fornecedor.

## 9. Criterios de aceite por modulo
- CRUD completo operando com validacao server-side.
- Permissao por papel aplicada em UI e API.
- Logs minimos de criacao/edicao/exclusao.
- Teste funcional dos principais fluxos.
- Exportacao CSV funcional para operacao administrativa.

## 10. Indicadores de sucesso
- Tempo medio para cadastrar associado <= 3 minutos.
- Taxa de erro de cadastro < 2%.
- Tempo para publicar comunicado <= 2 minutos.
- Tempo para criar evento e abrir inscricao <= 5 minutos.
- 100% de acoes sensiveis com registro de auditoria.

## 11. Referencias de benchmark (web)
1. CiviCRM Members (self-service, tipos/status de membership, renovacao): https://civicrm.org/members-0
2. CiviCRM Reports (relatorios, agendamento, exportacao, restricao por usuario): https://civicrm.org/reports
3. Glue Up (suite integrada: CRM, memberships, events, campaigns, chapter management): https://www.glueup.com/
4. Wild Apricot (politicas de renovacao e autoatendimento de membros): https://support.wildapricot.com/hc/en-us/articles/24302322019981-Membership-renewal-settings
5. Wild Apricot (renovacao manual e fluxo operacional admin): https://support.wildapricot.com/hc/en-us/articles/24302418793229-Manual-membership-renewal

## 12. Proximo passo pratico
Transformar este plano em backlog tecnico executavel:
- epicos -> historias -> tarefas tecnicas
- estimativa por modulo
- sequenciamento por dependencias (dados, API, UI, testes)
