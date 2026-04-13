# STATUS DO SISTEMA (ANATEJE) — O QUE FALTA FAZER

Data da analise: 2026-04-12
Objetivo: consolidar o sistema para operacao segura (governanca), reduzir divergencias entre modulos e fechar os ultimos epicos do backlog (relatorios + hardening + financeiro por dominio).

## 1) Onde estao os maiores “gaps” (por impacto)

Definicoes para leitura:
- Entropy (tendencia natural a degradar: divergencia, fragilidade, inconsistencias que crescem com o tempo)
- Negentropy (crescimento deliberado: padronizacao, automacao, coerencia que se acumula e facilita o futuro)
- Tacit knowledge (regras reais do negocio e operacao que ainda nao estao totalmente codificadas/especificadas)

### 1.1 Governanca: auditoria incompleta e fragmentada (alta prioridade)

Pendencias ja identificadas na auditoria tecnica:
- Unificar a trilha legada do financeiro (ex.: `transferencias.php`) para gravar em `audit_logs` (evitar “duas auditorias”).
- Fechar cobertura de auditoria onde falta:
  - Admin: `permissions.php` e `integrations.php` sem registro em `audit_logs`.
  - Financeiro: `orcamentos`, `receitas_despesas`, `contas_financeiras`, `contas` sem auditoria.
- Expor os detalhes gravados (`antes_json`, `depois_json`, `meta_json`) na API/UI de auditoria em modo “detalhe sob demanda”.
- Decidir politica por modulo: falha ao auditar bloqueia escrita (modo estrito) ou apenas registra erro (modo resiliente).

Referencia: `AUDITORIA-FLUXO-E-COBERTURA-CRUDS-04-03-2026.md`.

### 1.2 Financeiro: adaptacao de dominio escolar → associacao (alta prioridade)

Pendencias (conforme plano de migracao):
- Separar o que e “nucleo financeiro” (reaproveitamento direto) do que e “dominio escolar acoplado”.
- Substituir/reescrever modulos acoplados a escola para o dominio associativo:
  - `rematricula` → fluxo de renovacao de filiacao
  - `mensalidades_functions` e correlatos → modelo de cobranca/contribuicao do associado
  - `planos/contratos/cobrancas/pagamentos` → regras e vocabulario do ecossistema de associacoes
- Definir interfaces de integracao entre:
  - Associados (cadastro, status, categoria, contribuicao)
  - Financeiro (lancamentos, cobrancas, pagamentos)
  - Auditoria (trilha unica)

Risco (tacit knowledge): regras de “renovacao”, “inadimplencia”, “status do associado”, “categoria” e “contribuicao mensal” precisam ser formalizadas para evitar implementacao por suposicao.

Referencia: `PLANO-MIGRACAO-FINANCEIRO-03-03-2026.md`.

### 1.3 Seguranca: hardening e padronizacao (media/alta prioridade)

Pendencias (conforme backlog e diagnosticos):
- CSRF consistente e obrigatorio em todas as rotas de escrita (admin + financeiro).
- Politicas de sessao (expiracao, renovacao, protecao contra fixation) e limites operacionais por IP/usuario em rotas criticas.
- Normalizacao de contratos de resposta/erro nos endpoints legados do financeiro para reduzir divergencias.
- Tratamento de dados sensiveis em “integracoes” (ex.: evitar expor `api_key` em respostas quando nao for estritamente necessario).

Referencia: `BACKLOG-IMPLEMENTACAO-02-03-2026.md` (E32) e `PLANO-MIGRACAO-FINANCEIRO-03-03-2026.md` (Fase 2).

### 1.4 Relatorios e “inteligencia” (media prioridade)

Pendencias de fechamento do epico de visao gerencial:
- Consolidar relatorios/KPIs no dashboard (por periodo, com exportacoes onde fizer sentido).
- Definir quais relatorios sao “produto” (uso diario) vs “operacao” (auditoria/controle).
- Padronizar exportacoes por modulo (quando faltarem) e validar performance/limites.

Referencia: `BACKLOG-IMPLEMENTACAO-02-03-2026.md` (E30) e `UPDATE-02-03-2026.md` (dashboard ja aceita periodo).

### 1.5 Testes e disciplina TDD (media prioridade, alto retorno)

Pendencias recomendadas para consolidacao:
- Expandir suite de testes de integracao para fluxos criticos:
  - RBAC por acao (bloqueia corretamente)
  - Auditoria (gera trilha em create/update/delete e exporta com filtros)
  - Financeiro (ao menos nucleo operacional: lancamentos, pagamentos parciais, transferencias/alias)
- Fixar avisos `Deprecated` reportados no bootstrap (nao bloqueiam, mas geram ruido e risco futuro).
- Definir um “caminho feliz” de CI local: comando unico para rodar lint basico + phpunit.

Referencia: `UPDATE-05-03-2026.md`.

## 2) Ordem de execucao recomendada (sequencia enxuta)

1. Governanca e consistencia:
   - Unificar auditoria e fechar gaps (admin + financeiro).
2. Seguranca:
   - CSRF/contratos de erro e sessao em tudo que escreve.
3. Financeiro por dominio:
   - Adaptar “dominio escolar” para “dominio associativo” com regras explicitadas.
4. Relatorios:
   - Consolidar KPIs + exportacoes com filtro temporal.
5. Testes:
   - Aumentar cobertura nos fluxos acima para evitar regressao.

## 3) Definicao de pronto (DoD) para esta fase

- Auditoria unificada e visivel no admin para todos modulos sensiveis.
- Zero endpoints de escrita sem CSRF (quando aplicavel ao contexto de sessao).
- Financeiro MVP operacional funciona sem dependencias escolares obrigatorias.
- Suite de testes cobre os fluxos criticos e roda de forma repetivel.
