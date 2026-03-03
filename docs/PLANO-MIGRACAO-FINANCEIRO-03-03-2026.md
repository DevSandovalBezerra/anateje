# Plano de Migracao do Modulo Financeiro (LiderGest -> ANATEJE)

Data: 2026-03-03
Status: Planejamento aprovado para execucao
Escopo: Integrar o modulo financeiro copiado (`api/financeiro`, `frontend/financeiro`, `frontend/js/financeiro`) ao sistema administrativo ANATEJE, respeitando os padroes atuais.

## 1. Diagnostico consolidado

### 1.1 Origem e fluxo de referencia (LiderGest)
Arquivos principais analisados:
- `lidergest/includes/sidebar.php`
- `lidergest/includes/page_router.php`
- `lidergest/includes/rbac.php`
- `lidergest/index.php`
- `lidergest/api/financeiro/*`
- `lidergest/frontend/financeiro/*`
- `lidergest/frontend/js/financeiro/*`

Resumo:
- O LiderGest organiza o financeiro em grupos de menu (estrutura, operacional, planejamento, cobrancas, relatorios).
- O backend financeiro possui multiplos endpoints por `action` (listar/obter/criar/atualizar/excluir e acoes de dominio).
- O modulo foi desenhado para contexto multi-unidade e parte dele para dominio escolar (alunos/turmas/mensalidades).

### 1.2 Estado atual no ANATEJE (apos copia)
Pastas copiadas detectadas:
- `api/financeiro` (19 arquivos)
- `frontend/financeiro` (21 arquivos)
- `frontend/js/financeiro` (18 arquivos)

Gaps estruturais encontrados:
- `includes/page_router.php` ainda sem rotas financeiras.
- `includes/sidebar.php` ainda sem sequencia/meta de rotas financeiras.
- `includes/rbac.php` sem fallback claro para modulo `financeiro`.
- `index.php` sem titulos para paginas financeiras.
- Falta `config/unidade_helper.php` (dependencia direta de varios endpoints financeiros).

Gaps de compatibilidade tecnica:
- Parte do frontend financeiro esta em pagina FULL (`<!DOCTYPE html>...`) e nao em partial integrada ao layout.
- JS financeiro usa `fetch` direto, sem padrao `anatejeApi` + CSRF global.
- Alguns endpoints usam assinatura de permissao legada (`checkPermission('financeiro', $user['permissoes'])`) incompativel com o RBAC atual.
- Modulos `planos/contratos/cobrancas/pagamentos/rematricula/mensalidades` tem forte acoplamento escolar.

## 2. Objetivos de migracao

1. Tornar o financeiro navegavel no sistema ANATEJE (router + sidebar + titulos + RBAC).
2. Garantir compatibilidade minima sem quebrar o administrativo atual.
3. Evoluir para padrao tecnico ANATEJE (layout partial, API padronizada, CSRF, auditoria).
4. Adaptar o dominio escolar para dominio associativo em fase dedicada.

## 3. Plano de execucao por fases

## Fase 0 - Baseline tecnico (1 dia)
1. Consolidar inventario de arquivos e endpoints.
2. Congelar baseline da copia para comparacao.
3. Mapear dependencias externas ausentes (ex.: `api/cadastros/unidades.php`).

Criterio de aceite:
- Inventario tecnico versionado em `docs`.

## Fase 1 - Compatibilidade estrutural minima (2 dias)
1. Adicionar rotas financeiras no `includes/page_router.php`.
2. Incluir rotas financeiras na logica da `includes/sidebar.php`.
3. Ajustar fallback/menus do `includes/rbac.php` para reconhecer modulo financeiro.
4. Incluir titulos das paginas financeiras em `index.php`.
5. Criar `config/unidade_helper.php` com `getUserUnidadeId()` seguro.

Criterio de aceite:
- Usuario admin com permissao consegue abrir paginas `financeiro/*` pelo sidebar.
- Sem erro fatal por dependencia de `unidade_helper`.

## Fase 2 - Compatibilidade de seguranca e contrato API (3 dias)
1. Padronizar autenticacao/permissao dos endpoints financeiros.
2. Corrigir endpoints com verificacao legada de permissao.
3. Aplicar CSRF nas rotas de escrita.
4. Normalizar resposta/erros (envelope padrao do sistema).

Criterio de aceite:
- Todos endpoints financeiros de escrita exigem CSRF.
- Sem divergencia de permissao entre UI e API.

## Fase 3 - Adaptacao de UI para padrao ANATEJE (4 dias)
1. Converter paginas FULL para partial no layout comum.
2. Padronizar toolbars, acoes e feedbacks usando componentes do admin.
3. Trocar `fetch` direto por cliente comum (`anatejeApi` ou adaptador unico).

Criterio de aceite:
- Todas telas financeiras renderizam dentro do layout administrativo padrao.

## Fase 4 - Nucleo financeiro operacional (MVP) (5 dias)
Prioridade de modulos:
1. `contas_bancarias`
2. `pessoas`
3. `categorias_financeiras`
4. `centros_custos`
5. `lancamentos`
6. `pagamentos_parciais`
7. `transferencias`
8. `dashboard` e `fluxo_caixa`
9. `auditoria`

Criterio de aceite:
- Fluxo completo: cadastrar base -> lancar -> pagar parcial -> transferir -> visualizar dashboard.

## Fase 5 - Adaptacao de dominio (associacao) (6-8 dias)
Modulos com acoplamento escolar a adaptar:
- `planos`
- `contratos`
- `cobrancas`
- `pagamentos`
- `mensalidades_functions`
- `rematricula` (substituir por renovacao de filiacao)

Criterio de aceite:
- Nenhuma referencia obrigatoria a `alunos/turmas` para operacao financeira associativa.

## Fase 6 - Governanca e go-live (3 dias)
1. Matriz de permissao granular `financeiro.*`.
2. QA funcional e de regressao.
3. Checklist de rollout e monitoramento.

Criterio de aceite:
- Liberacao controlada em producao com validacao de seguranca e trilha de auditoria.

## 4. Matriz de reaproveitamento

### Reaproveitamento direto (baixo esforco)
- `contas_bancarias`, `pessoas`, `categorias_financeiras`, `centros_custos`, `lancamentos`, `pagamentos_parciais`, `transferencias`, `dashboard`, `fluxo_caixa`, `auditoria`.

### Reaproveitamento medio (ajustes tecnicos)
- `orcamentos`, `receitas_despesas`, `contas_financeiras`, `contas` (legado).

### Reescrita/adaptacao de dominio (alto esforco)
- `planos`, `contratos`, `cobrancas`, `pagamentos`, `mensalidades_functions`, `rematricula`.

## 5. Riscos e mitigacao

1. Risco: Dependencias escolares quebrando fluxo associativo.
- Mitigacao: encapsular e adaptar em Fase 5 com feature flag.

2. Risco: Divergencia de seguranca entre modulo novo e padrao atual.
- Mitigacao: Fase 2 obrigatoria antes de liberar operacao financeira sensivel.

3. Risco: UI mista (full page vs partial) gerar inconsistencias de UX.
- Mitigacao: Fase 3 para uniformizacao completa.

## 6. Sequencia recomendada imediata

1. Executar Fase 1.
2. Validar navegacao e permissao minima.
3. Iniciar Fase 2 sem intervalo.
