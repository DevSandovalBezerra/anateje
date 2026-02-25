# Migrations SQL - ANATEJE

Esta pasta concentra o schema base e as futuras migrations.

## Padrao de arquivos
- `0001_initial_schema.sql`: schema inicial completo do projeto.
- `0002_*.sql`, `0003_*.sql`, ...: migrations incrementais.

## Convencao
- Use prefixo numerico crescente com 4 digitos.
- Cada migration deve ser idempotente quando possivel (`IF NOT EXISTS`, `IF EXISTS`).
- Evite alterar migrations antigas ja aplicadas em ambientes compartilhados.
- Novas mudancas estruturais devem entrar em um novo arquivo, nunca reescrever o historico.

## Ordem de execucao
Aplicar em ordem lexicografica (nome do arquivo), do menor para o maior.

## Execucao via CLI
- Aplicar pendentes:
  - `php config/sql/migrate.php up`
- Ver status:
  - `php config/sql/migrate.php status`

## Controle de versao
- O runner grava historico em `schema_migrations` (no proprio banco).
- Cada arquivo recebe checksum SHA-256 ao aplicar.
- Se um arquivo ja aplicado for alterado, o runner bloqueia a execucao.
