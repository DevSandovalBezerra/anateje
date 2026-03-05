-- Cleanup de contaminacao do banco lidergest por bootstrap/seed do anateje
-- Data de referencia da contaminacao:
--   Permissoes: 2026-03-03 15:25:03
--   Benefits:   2026-03-03 15:20:06
--
-- Execute com usuario que tenha permissao de DELETE/CREATE/INSERT no banco.
-- Exemplo:
-- mysql -u root < cleanup_lidergest_contaminacao_anateje_20260303.sql

USE brunor90_lidergest;

SET @perm_seed_ts := '2026-03-03 15:25:03';
SET @benefits_seed_ts := '2026-03-03 15:20:06';

-- 1) Pre-check (o que sera afetado)
SELECT
    'precheck_permissoes_alvo' AS item,
    COUNT(*) AS total
FROM permissoes p
WHERE p.created_at = @perm_seed_ts
  AND p.modulo IN (
      'admin',
      'admin_associados',
      'admin_auditoria',
      'admin_beneficios',
      'admin_campanhas',
      'admin_comunicados',
      'admin_eventos',
      'admin_pastas_associados',
      'admin_permissoes',
      'associado',
      'cadastros',
      'dashboard'
  );

SELECT
    'precheck_links_perfil_permissoes_alvo' AS item,
    COUNT(*) AS total
FROM perfil_permissoes pp
WHERE pp.permissao_id IN (
    SELECT p.id
    FROM permissoes p
    WHERE p.created_at = @perm_seed_ts
      AND p.modulo IN (
          'admin',
          'admin_associados',
          'admin_auditoria',
          'admin_beneficios',
          'admin_campanhas',
          'admin_comunicados',
          'admin_eventos',
          'admin_pastas_associados',
          'admin_permissoes',
          'associado',
          'cadastros',
          'dashboard'
      )
);

SELECT
    'precheck_benefits_alvo' AS item,
    COUNT(*) AS total
FROM benefits b
WHERE b.created_at = @benefits_seed_ts
  AND b.nome IN (
      'Assessoria Juridica',
      'Telemedicina Byteclin',
      'Ambulatej',
      'Mestrado Cesara',
      'Byte Club Descontos',
      'Wellhub / Gympass',
      'Instituto ITES',
      'TIM Telefonia'
  );

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_cleanup_perm_ids;
CREATE TEMPORARY TABLE tmp_cleanup_perm_ids (
    id INT NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT INTO tmp_cleanup_perm_ids (id)
SELECT p.id
FROM permissoes p
WHERE p.created_at = @perm_seed_ts
  AND p.modulo IN (
      'admin',
      'admin_associados',
      'admin_auditoria',
      'admin_beneficios',
      'admin_campanhas',
      'admin_comunicados',
      'admin_eventos',
      'admin_pastas_associados',
      'admin_permissoes',
      'associado',
      'cadastros',
      'dashboard'
  );

-- 2) Backup de seguranca antes de remover
CREATE TABLE IF NOT EXISTS backup_cleanup_20260303_permissoes LIKE permissoes;
CREATE TABLE IF NOT EXISTS backup_cleanup_20260303_perfil_permissoes LIKE perfil_permissoes;
CREATE TABLE IF NOT EXISTS backup_cleanup_20260303_benefits LIKE benefits;

INSERT IGNORE INTO backup_cleanup_20260303_permissoes
SELECT p.*
FROM permissoes p
INNER JOIN tmp_cleanup_perm_ids t ON t.id = p.id;

INSERT IGNORE INTO backup_cleanup_20260303_perfil_permissoes
SELECT pp.*
FROM perfil_permissoes pp
INNER JOIN tmp_cleanup_perm_ids t ON t.id = pp.permissao_id;

INSERT IGNORE INTO backup_cleanup_20260303_benefits
SELECT b.*
FROM benefits b
WHERE b.created_at = @benefits_seed_ts
  AND b.nome IN (
      'Assessoria Juridica',
      'Telemedicina Byteclin',
      'Ambulatej',
      'Mestrado Cesara',
      'Byte Club Descontos',
      'Wellhub / Gympass',
      'Instituto ITES',
      'TIM Telefonia'
  );

-- 3) Remocao (filho -> pai)
DELETE pp
FROM perfil_permissoes pp
INNER JOIN tmp_cleanup_perm_ids t ON t.id = pp.permissao_id;

DELETE p
FROM permissoes p
INNER JOIN tmp_cleanup_perm_ids t ON t.id = p.id;

DELETE b
FROM benefits b
WHERE b.created_at = @benefits_seed_ts
  AND b.nome IN (
      'Assessoria Juridica',
      'Telemedicina Byteclin',
      'Ambulatej',
      'Mestrado Cesara',
      'Byte Club Descontos',
      'Wellhub / Gympass',
      'Instituto ITES',
      'TIM Telefonia'
  );

COMMIT;

-- 4) Post-check
SELECT
    'postcheck_permissoes_alvo_restantes' AS item,
    COUNT(*) AS total
FROM permissoes p
WHERE p.created_at = @perm_seed_ts
  AND p.modulo IN (
      'admin',
      'admin_associados',
      'admin_auditoria',
      'admin_beneficios',
      'admin_campanhas',
      'admin_comunicados',
      'admin_eventos',
      'admin_pastas_associados',
      'admin_permissoes',
      'associado',
      'cadastros',
      'dashboard'
  );

SELECT
    'postcheck_links_restantes' AS item,
    COUNT(*) AS total
FROM perfil_permissoes pp
WHERE pp.permissao_id IN (SELECT id FROM backup_cleanup_20260303_permissoes);

SELECT
    'postcheck_benefits_alvo_restantes' AS item,
    COUNT(*) AS total
FROM benefits b
WHERE b.created_at = @benefits_seed_ts
  AND b.nome IN (
      'Assessoria Juridica',
      'Telemedicina Byteclin',
      'Ambulatej',
      'Mestrado Cesara',
      'Byte Club Descontos',
      'Wellhub / Gympass',
      'Instituto ITES',
      'TIM Telefonia'
  );

SELECT
    'backup_permissoes' AS item,
    COUNT(*) AS total
FROM backup_cleanup_20260303_permissoes
UNION ALL
SELECT
    'backup_perfil_permissoes' AS item,
    COUNT(*) AS total
FROM backup_cleanup_20260303_perfil_permissoes
UNION ALL
SELECT
    'backup_benefits' AS item,
    COUNT(*) AS total
FROM backup_cleanup_20260303_benefits;

