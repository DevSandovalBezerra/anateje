-- Usuarios: base de autenticacao + classificacao de funcionario
-- Objetivo: suportar tipos de funcionario (contador, atendente, etc.)

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil_id INT UNSIGNED NOT NULL DEFAULT 2,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  unidade_id BIGINT UNSIGNED NULL,
  tipo_usuario ENUM('ASSOCIADO','FUNCIONARIO','ADMIN') NOT NULL DEFAULT 'ASSOCIADO',
  tipo_funcionario ENUM(
    'CONTADOR',
    'ATENDENTE',
    'FINANCEIRO',
    'COORDENACAO',
    'SUPORTE',
    'GESTOR',
    'OUTRO'
  ) NULL,
  ultimo_login DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_email (email),
  KEY idx_usuarios_perfil_id (perfil_id),
  KEY idx_usuarios_ativo (ativo),
  KEY idx_usuarios_tipo_usuario (tipo_usuario),
  KEY idx_usuarios_tipo_funcionario (tipo_funcionario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibilidade para bancos onde a tabela usuarios ja existe
SET @has_nome := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'nome'
);
SET @sql := IF(@has_nome = 0, 'ALTER TABLE usuarios ADD COLUMN nome VARCHAR(150) NOT NULL DEFAULT ''''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_email := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'email'
);
SET @sql := IF(@has_email = 0, 'ALTER TABLE usuarios ADD COLUMN email VARCHAR(190) NOT NULL DEFAULT ''''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_senha := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'senha'
);
SET @sql := IF(@has_senha = 0, 'ALTER TABLE usuarios ADD COLUMN senha VARCHAR(255) NOT NULL DEFAULT ''''', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_perfil_id := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'perfil_id'
);
SET @sql := IF(@has_perfil_id = 0, 'ALTER TABLE usuarios ADD COLUMN perfil_id INT UNSIGNED NOT NULL DEFAULT 2', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_ativo := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'ativo'
);
SET @sql := IF(@has_ativo = 0, 'ALTER TABLE usuarios ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_unidade_id := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'unidade_id'
);
SET @sql := IF(@has_unidade_id = 0, 'ALTER TABLE usuarios ADD COLUMN unidade_id BIGINT UNSIGNED NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_tipo_usuario := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'tipo_usuario'
);
SET @sql := IF(
  @has_tipo_usuario = 0,
  'ALTER TABLE usuarios ADD COLUMN tipo_usuario ENUM(''ASSOCIADO'',''FUNCIONARIO'',''ADMIN'') NOT NULL DEFAULT ''ASSOCIADO''',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_tipo_funcionario := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'tipo_funcionario'
);
SET @sql := IF(
  @has_tipo_funcionario = 0,
  'ALTER TABLE usuarios ADD COLUMN tipo_funcionario ENUM(''CONTADOR'',''ATENDENTE'',''FINANCEIRO'',''COORDENACAO'',''SUPORTE'',''GESTOR'',''OUTRO'') NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_ultimo_login := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'ultimo_login'
);
SET @sql := IF(@has_ultimo_login = 0, 'ALTER TABLE usuarios ADD COLUMN ultimo_login DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_created_at := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'created_at'
);
SET @sql := IF(@has_created_at = 0, 'ALTER TABLE usuarios ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_updated_at := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND column_name = 'updated_at'
);
SET @sql := IF(@has_updated_at = 0, 'ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Indices de apoio (nao destrutivos)
SET @has_idx_email := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND index_name = 'idx_usuarios_email'
);
SET @sql := IF(@has_idx_email = 0, 'ALTER TABLE usuarios ADD KEY idx_usuarios_email (email)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_perfil := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND index_name = 'idx_usuarios_perfil_id'
);
SET @sql := IF(@has_idx_perfil = 0, 'ALTER TABLE usuarios ADD KEY idx_usuarios_perfil_id (perfil_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_ativo := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND index_name = 'idx_usuarios_ativo'
);
SET @sql := IF(@has_idx_ativo = 0, 'ALTER TABLE usuarios ADD KEY idx_usuarios_ativo (ativo)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_tipo_usuario := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND index_name = 'idx_usuarios_tipo_usuario'
);
SET @sql := IF(@has_idx_tipo_usuario = 0, 'ALTER TABLE usuarios ADD KEY idx_usuarios_tipo_usuario (tipo_usuario)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx_tipo_funcionario := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'usuarios'
    AND index_name = 'idx_usuarios_tipo_funcionario'
);
SET @sql := IF(@has_idx_tipo_funcionario = 0, 'ALTER TABLE usuarios ADD KEY idx_usuarios_tipo_funcionario (tipo_funcionario)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

