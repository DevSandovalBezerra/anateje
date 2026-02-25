-- ANATEJE MVP schema (baseline)

CREATE TABLE IF NOT EXISTS members (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  nome VARCHAR(150) NOT NULL,
  lotacao VARCHAR(150) NULL,
  cargo VARCHAR(150) NULL,
  cpf CHAR(11) NOT NULL,
  data_filiacao DATE NULL,
  categoria ENUM('PARCIAL','INTEGRAL') NOT NULL DEFAULT 'PARCIAL',
  status ENUM('ATIVO','INATIVO') NOT NULL DEFAULT 'ATIVO',
  contribuicao_mensal DECIMAL(10,2) NULL,
  matricula VARCHAR(60) NULL,
  telefone VARCHAR(30) NULL,
  email_funcional VARCHAR(190) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_members_user_id (user_id),
  UNIQUE KEY uk_members_cpf (cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS addresses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NOT NULL,
  cep CHAR(8) NOT NULL,
  logradouro VARCHAR(190) NULL,
  numero VARCHAR(30) NULL,
  complemento VARCHAR(60) NULL,
  bairro VARCHAR(120) NULL,
  cidade VARCHAR(120) NULL,
  uf CHAR(2) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_addresses_member_id (member_id),
  KEY idx_addresses_cep (cep)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(150) NOT NULL,
  descricao TEXT NULL,
  link VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_benefits_status (status),
  KEY idx_benefits_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_benefits (
  member_id BIGINT UNSIGNED NOT NULL,
  benefit_id BIGINT UNSIGNED NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id, benefit_id),
  KEY idx_member_benefits_benefit_id (benefit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  titulo VARCHAR(190) NOT NULL,
  descricao TEXT NULL,
  local VARCHAR(190) NULL,
  inicio_em DATETIME NOT NULL,
  fim_em DATETIME NULL,
  vagas INT NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  imagem_url VARCHAR(255) NULL,
  link VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_events_inicio_em (inicio_em),
  KEY idx_events_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id BIGINT UNSIGNED NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  status ENUM('registered','canceled') NOT NULL DEFAULT 'registered',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_event_member (event_id, member_id),
  KEY idx_event_regs_member_id (member_id),
  KEY idx_event_regs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo ENUM('BLOG','COMUNICADO') NOT NULL DEFAULT 'COMUNICADO',
  titulo VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NULL,
  conteudo LONGTEXT NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  publicado_em DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_posts_slug (slug),
  KEY idx_posts_tipo_status (tipo, status),
  KEY idx_posts_publicado_em (publicado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  canal ENUM('INAPP','EMAIL','WHATSAPP') NOT NULL,
  titulo VARCHAR(190) NOT NULL,
  payload_json LONGTEXT NULL,
  filtro_json LONGTEXT NULL,
  status ENUM('draft','queued','processing','done','failed') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_campaigns_canal_status (canal, status),
  KEY idx_campaigns_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  run_id BIGINT UNSIGNED NULL,
  member_id BIGINT UNSIGNED NULL,
  canal ENUM('INAPP','EMAIL','WHATSAPP') NOT NULL,
  destino VARCHAR(190) NOT NULL,
  status ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  erro TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_campaign_logs_campaign_id (campaign_id),
  KEY idx_campaign_logs_run_id (run_id),
  KEY idx_campaign_logs_campaign_run (campaign_id, run_id),
  KEY idx_campaign_logs_member_id (member_id),
  KEY idx_campaign_logs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaign_runs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  status ENUM('processing','done','failed') NOT NULL DEFAULT 'processing',
  total_count INT NOT NULL DEFAULT 0,
  queued_count INT NOT NULL DEFAULT 0,
  sent_count INT NOT NULL DEFAULT 0,
  failed_count INT NOT NULL DEFAULT 0,
  skipped_count INT NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_campaign_runs_campaign_id (campaign_id),
  KEY idx_campaign_runs_status (status),
  KEY idx_campaign_runs_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS integration_settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider ENUM('MAILCHIMP','WHATSAPP') NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  api_key VARCHAR(255) NULL,
  endpoint VARCHAR(255) NULL,
  sender VARCHAR(120) NULL,
  config_json LONGTEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_integration_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'Assessoria Juridica', NULL, NULL, 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'Assessoria Juridica');
INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'Telemedicina Byteclin', NULL, NULL, 'active', 2
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'Telemedicina Byteclin');
INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'Ambulatej', NULL, NULL, 'active', 3
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'Ambulatej');
INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'Mestrado Cesara', NULL, NULL, 'active', 4
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'Mestrado Cesara');
INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'Byte Club Descontos', NULL, NULL, 'active', 5
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'Byte Club Descontos');
INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'Wellhub / Gympass', NULL, NULL, 'active', 6
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'Wellhub / Gympass');
INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'Instituto ITES', NULL, NULL, 'active', 7
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'Instituto ITES');
INSERT INTO benefits (nome, descricao, link, status, sort_order)
SELECT 'TIM Telefonia', NULL, NULL, 'active', 8
WHERE NOT EXISTS (SELECT 1 FROM benefits WHERE nome = 'TIM Telefonia');
