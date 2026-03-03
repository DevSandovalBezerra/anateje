# PRD — ANATEJE
Portal institucional + promocional (landing) e sistema de associação (área de membros + admin)

Versão: 1.0

## 1. Visão do Produto

### 1.1 Objetivo
Construir um portal público (institucional + conversão) e um sistema de gestão de associados para a ANATEJE, com:
- Site institucional e promocional (quase landing-page)
- Área do associado: cadastro, status, plano, benefícios, eventos, comunicados
- Administração: gestão de associados, benefícios, eventos, comunicados, campanhas e integrações
- Disparos: WhatsApp (provedor oficial) e Email (Mailchimp)

### 1.2 Público-alvo (personas)
- Visitante: quer entender a associação e seus benefícios e se filiar
- Associado: atualiza dados, acompanha status, benefícios, eventos, comunicados
- Admin (secretaria/financeiro/comunicação): gerencia cadastros, conteúdo, eventos e campanhas

### 1.3 Diretrizes visuais (MIV)
- Tipografia
  - Títulos: League Spartan
  - Textos: Open Sans
- Paleta (tokens sugeridos)
  - Fundo: #0d0702
  - Neutro: #696969
  - Vinho: #5c2620
  - Dourado: #a6764c
  - Azul: #262353
- Estilo
  - Dark + gradientes
  - Seções grandes, blocos com cards
  - CTAs fortes e repetidos ao longo da página

## 2. Escopo

### 2.1 MVP (primeira entrega)
- Site institucional (home estilo landing + páginas principais)
- Cadastro e login de associado
- Perfil do associado (campos completos + endereço por CEP)
- Planos/categorias: Parcial (0,5%) e Integral (1%)
- Benefícios (catálogo + benefícios ativos por associado)
- Eventos (lista, detalhe, inscrição)
- Comunicados (feed no painel do associado)
- Admin básico (CRUD associados, benefícios, eventos, comunicados)
- Integrações
  - Mailchimp: sincronizar contatos/tags
  - WhatsApp: broadcast via provedor com fila + logs

### 2.2 Pós-MVP (fase 2)
- Pagamentos/assinaturas recorrentes (gateway)
- Carteirinha digital + QR Code
- Blog completo + SEO
- Documentos com permissões (atas, convênios, editais)
- Relatórios avançados e RBAC (perfis admin)
- Automação de campanhas e segmentações avançadas

## 3. Requisitos Funcionais

### 3.1 Site público (institucional + promocional)
Páginas:
- Home (landing)
  - Hero com CTA “Associe-se”
  - Blocos: Quem somos, Benefícios, Como funciona, Eventos, Notícias, FAQ, Rodapé
- Sobre
- Benefícios (catálogo)
- Eventos (agenda pública resumida)
- Blog/Notícias
- Contato

Conversão:
- CTA “Quero me filiar” → /filiacao.php (pré-cadastro) e/ou /login.php
- Captura de leads (Mailchimp): nome, email, telefone, UF

### 3.2 Autenticação e sessão
- Login por email funcional + senha
- Recuperação de senha por email
- Controle de sessão (expiração, revogação)
- Rate limit no login

### 3.3 Cadastro do associado (campos)
- Nome
- Lotação
- Cargo
- CPF (validar e evitar duplicidade)
- Data de filiação
- Categoria: Parcial (0,5%) / Integral (1%)
- Status: ativo/inativo
- Contribuição mensal: valor líquido (R$)
- Matrícula
- Telefone
- E-mail funcional
- Endereço: buscar e autopreencher pelo CEP (ViaCEP)

### 3.4 Benefícios
- Cadastro de benefícios (admin): nome, descrição, link, status, regras
- Benefícios ativos por associado (checkbox/flags)
- Benefícios do quadro (inicial)
  - Assessoria Jurídica
  - Telemedicina (Byteclin)
  - Ambulatej
  - Mestrado Cesara
  - Byte Club (descontos)
  - Wellhub/Gympass
  - Instituto ITES
  - TIM Telefonia

### 3.5 Eventos
- Admin cria evento: título, descrição, local (presencial/online), data/hora, vagas, imagem, link, status
- Associado:
  - Lista e detalhe do evento
  - Inscrição/cancelamento
- Admin:
  - Lista de inscritos
  - Exportar CSV

### 3.6 Comunicados e Broadcast (WhatsApp + Email)
Tipos:
- In-app (comunicado dentro do painel)
- Email (Mailchimp)
- WhatsApp (provedor)

Segmentação:
- Categoria (PARCIAL/INTEGRAL)
- Status (ativo/inativo)
- UF/lotação (se aplicável)
- Benefício ativo

Logs e opt-out:
- Logs por destinatário: status, erro, timestamp
- Flags de não recebimento (whatsapp) e opt-out (mailchimp)

### 3.7 Administração (painel)
Módulos:
- Associados (CRUD, ativar/inativar, exportar)
- Benefícios (CRUD)
- Eventos (CRUD, inscritos)
- Comunicados (CRUD, publicar/arquivar)
- Campanhas (criar disparos, segmentar, logs)
- Integrações (chaves e status)
- Auditoria (ações admin)

## 4. Requisitos Não Funcionais
- Segurança
  - password_hash/password_verify
  - Middleware auth
  - Rate limit
  - Validação server-side
  - CSRF em formulários
- LGPD
  - Consentimento e registro
  - Política de privacidade
- Performance
  - Cache simples em páginas públicas
  - Paginação
- Observabilidade
  - Logs (arquivo + tabelas)
  - Modo debug por env

## 5. Arquitetura Técnica

### 5.1 Padrão do projeto
- Frontend
  - Páginas .php com HTML e includes (header/footer)
  - Fetch JS para consumir API
- Backend
  - API PHP (JSON)
  - Middleware (auth, cors, rate limit)
  - PDO + transações
- UI
  - Tailwind + DaisyUI

### 5.2 Estrutura de pastas (sugestão)
```
/public
  index.php
  sobre.php
  beneficios.php
  eventos.php
  blog.php
  contato.php
  filiacao.php
  login.php
  dashboard.php
  /assets
    /css
    /js
    /img

/app
  /api
    /v1
      auth.php
      members.php
      benefits.php
      events.php
      posts.php
      campaigns.php
      utils.php
  /middleware
    auth_middleware.php
    cors.php
    rate_limit.php
  /services
    MailchimpService.php
    WhatsAppService.php
    ViaCepService.php
  /repositories
    MemberRepo.php
    BenefitRepo.php
    EventRepo.php
    PostRepo.php
  /config
    env.php
    database.php
  /views
    header.php
    footer.php
    navbar.php

/admin
  dashboard.php
  associados.php
  eventos.php
  beneficios.php
  comunicados.php
  campanhas.php
  integracoes.php
```

### 5.3 Autenticação recomendada
- JWT (para fetch) ou sessão PHP (com cookies seguros)
- MVP: JWT simples + middleware

## 6. Modelo de Dados (MySQL)

### 6.1 Tabelas principais (MVP)
- users
  - id, name, email, password_hash, role (admin/assoc), status, created_at
- members
  - id, user_id, nome, lotacao, cargo, cpf, data_filiacao, categoria, status, contribuicao_mensal, matricula, telefone, email_funcional
- addresses
  - id, member_id, cep, logradouro, numero, complemento, bairro, cidade, uf
- benefits
  - id, nome, descricao, link, status
- member_benefits
  - member_id, benefit_id, ativo, created_at
- events
  - id, titulo, descricao, local, inicio_em, fim_em, vagas, status, imagem_url, link
- event_registrations
  - id, event_id, member_id, status, created_at
- posts
  - id, tipo (BLOG/COMUNICADO), titulo, slug, conteudo, status, publicado_em
- campaigns
  - id, canal (EMAIL/WHATSAPP/INAPP), titulo, payload_json, filtro_json, status, created_at
- campaign_logs
  - id, campaign_id, canal, destino, status, erro, created_at
- audit_logs
  - id, user_id, acao, entidade, entidade_id, diff_json, created_at, ip

## 7. Integrações

### 7.1 ViaCEP
- Endpoint interno: /api/v1/utils.php?action=viacep&cep=xxxxx-xxx
- Preenche logradouro/bairro/cidade/UF
- Usuário confirma e salva

### 7.2 Mailchimp
- Sync contato quando:
  - lead capturado
  - associado criado/atualizado
- Tags sugeridas:
  - categoria: PARCIAL / INTEGRAL
  - status: ATIVO / INATIVO
  - UF
  - benefícios ativos

### 7.3 WhatsApp
- Provedor oficial (Cloud API ou parceiro)
- Templates aprovados
- Fila de envio + logs por destinatário
- Opt-out interno

## 8. UX/UI — Sitemap e Wireframe textual

### 8.1 Sitemap (MVP)
Público:
- /index.php
- /sobre.php
- /beneficios.php
- /eventos.php
- /blog.php
- /contato.php
- /filiacao.php
- /login.php

Privado (associado):
- /dashboard.php
- /perfil.php
- /meus-beneficios.php
- /meus-eventos.php
- /comunicados.php

Admin:
- /admin/dashboard.php
- /admin/associados.php
- /admin/beneficios.php
- /admin/eventos.php
- /admin/comunicados.php
- /admin/campanhas.php
- /admin/integracoes.php

### 8.2 Wireframe textual — Home (landing)
1) Navbar
- Logo + links (Sobre, Benefícios, Eventos, Blog, Contato)
- Botão: Entrar
- Botão CTA: Associe-se

2) Hero
- Headline: “Construindo o futuro…”
- Subheadline curta
- CTA primário: Quero me filiar
- CTA secundário: Ver benefícios

3) Bloco “Por que ANATEJE”
- 3 cards: Representatividade, Benefícios, Eventos

4) Bloco Benefícios (preview)
- Grid de cards (6–8)
- Link: Ver todos

5) Bloco Eventos
- 3 cards de próximos eventos
- CTA: Ver agenda

6) Bloco Depoimentos / Prova social (opcional)

7) FAQ
- Accordion DaisyUI

8) Rodapé
- Contatos, links úteis, política, redes

## 9. Critérios de Aceite (exemplos)
- CPF é validado e não duplica
- CEP consulta ViaCEP e preenche endereço
- Benefícios cadastrados no admin aparecem no público e painel
- Associado se inscreve em evento e admin exporta CSV
- Campanhas geram logs por destinatário e registram falhas
- Endpoints protegidos por middleware e não vazam dados

## 10. Plano de Entrega (fases)

### Fase 1 — MVP
- Base do projeto (layout + auth + API)
- CRUD associado + ViaCEP
- Dashboard de membros (painel inicial do associado)
- Benefícios + ativação por associado
- Eventos + inscrições
- Comunicados
- Dashboard administrativo (visão operacional)
- Admin básico (CRUDs + gestão diária)
- Integrações (Mailchimp sync + WhatsApp logs)

### Fase 2
- Pagamentos recorrentes
- Carteirinha QR
- Blog SEO
- Documentos com permissões
- Relatórios e RBAC
- Automação de campanhas

### 10.1 Fluxo de implementação dos dashboards (MVP)
1) Dashboard de membros (`/dashboard.php`)
- Objetivo: centralizar status de filiação, categoria, benefícios ativos, eventos e comunicados.
- Dependências: login JWT funcional, endpoint `members.php?action=get`, listagem de eventos e comunicados.
- Entrega mínima: cards de resumo + atalhos para `perfil.php`, `meus-beneficios.php`, `meus-eventos.php` e `comunicados.php`.

2) Dashboard administrativo (`/admin/dashboard.php`)
- Objetivo: visão de operação para secretaria/financeiro/comunicação.
- Dependências: autenticação com role `admin`, agregações básicas de associados/eventos/comunicados/campanhas.
- Entrega mínima: KPIs (associados ativos/inativos, próximos eventos, comunicados publicados, campanhas recentes) + atalhos para módulos admin.

3) Critério de conclusão da etapa
- Ambos dashboards com controle de acesso por perfil (assoc/admin).
- Navegação e layout consistentes com o design system.
- Dados carregados por API JSON com tratamento de erro padronizado.

## 11. Esqueleto de implementação (pronto para codar)

### 11.1 Rotas API (MVP)
- POST /app/api/v1/auth.php?action=login
- GET  /app/api/v1/auth.php?action=me
- POST /app/api/v1/auth.php?action=logout

- GET  /app/api/v1/members.php?action=get
- POST /app/api/v1/members.php?action=update

- GET  /app/api/v1/benefits.php?action=list
- POST /app/api/v1/benefits.php?action=set_member_benefits

- GET  /app/api/v1/events.php?action=list
- GET  /app/api/v1/events.php?action=detail&id=123
- POST /app/api/v1/events.php?action=register
- POST /app/api/v1/events.php?action=cancel

- GET  /app/api/v1/posts.php?action=list&type=COMUNICADO

- POST /app/api/v1/campaigns.php?action=create
- GET  /app/api/v1/campaigns.php?action=logs&id=123

- GET  /app/api/v1/utils.php?action=viacep&cep=xxxxx

### 11.2 Páginas privadas recomendadas (MVP)
- dashboard.php
  - cards: Status, Categoria, Benefícios ativos, Próximos eventos, Últimos comunicados
- perfil.php
  - form completo + CEP
- meus-beneficios.php
- meus-eventos.php
- comunicados.php

### 11.3 Convenções
- Todas operações de escrita: PDO com transação
- Resposta API sempre JSON
- Erros padronizados:
  - { ok: false, error: { code, message, details } }
- Sucesso:
  - { ok: true, data: ... }

---

Se quiser, no próximo passo eu posso transformar isso em:
- Checklist técnico (tarefas por módulo)
- Banco SQL (CREATE TABLE) do MVP
- Base de código (arquivos iniciais) com Tailwind + DaisyUI + Auth + Middleware + 1 módulo funcional (perfil + ViaCEP)



## 12. Banco de dados (MVP) — SQL (MySQL)

Observações:
- Charset recomendado: utf8mb4
- Engine: InnoDB
- CPF com UNIQUE
- Logs e auditoria com JSON

```sql
CREATE DATABASE IF NOT EXISTS anateje
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE anateje;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','assoc') NOT NULL DEFAULT 'assoc',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_status (status)
) ENGINE=InnoDB;

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
  UNIQUE KEY uk_members_cpf (cpf),
  UNIQUE KEY uk_members_user (user_id),
  KEY idx_members_categoria (categoria),
  KEY idx_members_status (status),
  CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

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
  UNIQUE KEY uk_addresses_member (member_id),
  KEY idx_addresses_cep (cep),
  CONSTRAINT fk_addresses_member FOREIGN KEY (member_id) REFERENCES members(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

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
  KEY idx_benefits_sort (sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS member_benefits (
  member_id BIGINT UNSIGNED NOT NULL,
  benefit_id BIGINT UNSIGNED NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (member_id, benefit_id),
  KEY idx_mb_benefit (benefit_id),
  CONSTRAINT fk_mb_member FOREIGN KEY (member_id) REFERENCES members(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_mb_benefit FOREIGN KEY (benefit_id) REFERENCES benefits(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

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
  KEY idx_events_inicio (inicio_em),
  KEY idx_events_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id BIGINT UNSIGNED NOT NULL,
  member_id BIGINT UNSIGNED NOT NULL,
  status ENUM('registered','canceled') NOT NULL DEFAULT 'registered',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_event_member (event_id, member_id),
  KEY idx_event_regs_member (member_id),
  KEY idx_event_regs_status (status),
  CONSTRAINT fk_event_regs_event FOREIGN KEY (event_id) REFERENCES events(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_event_regs_member FOREIGN KEY (member_id) REFERENCES members(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

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
  KEY idx_posts_tipo (tipo),
  KEY idx_posts_status (status),
  KEY idx_posts_publicado (publicado_em)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaigns (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  canal ENUM('INAPP','EMAIL','WHATSAPP') NOT NULL,
  titulo VARCHAR(190) NOT NULL,
  payload_json JSON NULL,
  filtro_json JSON NULL,
  status ENUM('draft','queued','processing','done','failed') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_campaigns_canal (canal),
  KEY idx_campaigns_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaign_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  campaign_id BIGINT UNSIGNED NOT NULL,
  canal ENUM('INAPP','EMAIL','WHATSAPP') NOT NULL,
  destino VARCHAR(190) NOT NULL,
  status ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  erro TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_camp_logs_campaign (campaign_id),
  KEY idx_camp_logs_status (status),
  CONSTRAINT fk_camp_logs_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  acao VARCHAR(120) NOT NULL,
  entidade VARCHAR(120) NULL,
  entidade_id BIGINT UNSIGNED NULL,
  diff_json JSON NULL,
  ip VARCHAR(60) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_entidade (entidade, entidade_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO benefits (nome, descricao, link, status, sort_order) VALUES
('Assessoria Jurídica', NULL, NULL, 'active', 1),
('Telemedicina Byteclin', NULL, NULL, 'active', 2),
('Ambulatej', NULL, NULL, 'active', 3),
('Mestrado Cesara', NULL, NULL, 'active', 4),
('Byte Club Descontos', NULL, NULL, 'active', 5),
('Wellhub / Gympass', NULL, NULL, 'active', 6),
('Instituto ITES', NULL, NULL, 'active', 7),
('TIM Telefonia', NULL, NULL, 'active', 8);
```

## 13. Esqueleto de código (MVP) — PHP API + Middleware + Fetch

Objetivo:
- Páginas .php com HTML puro
- API PHP (JSON)
- Middleware
- Fetch (JS)
- Módulo pronto: Perfil + ViaCEP

### 13.1 Config

#### /app/config/env.php
```php
<?php
return [
  'APP_ENV' => 'local',
  'APP_URL' => 'http://localhost',
  'DB_HOST' => '127.0.0.1',
  'DB_NAME' => 'anateje',
  'DB_USER' => 'root',
  'DB_PASS' => '',
  'JWT_SECRET' => 'troque-este-segredo-super-forte',
  'JWT_TTL_SECONDS' => 60 * 60 * 8
];
```

#### /app/config/database.php
```php
<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $env = require __DIR__ . '/env.php';
  $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'], $env['DB_NAME']);

  $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  return $pdo;
}
```

### 13.2 Middleware

#### /app/middleware/cors.php
```php
<?php
function cors(): void {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}
```

#### /app/services/Jwt.php
```php
<?php
function base64url_encode(string $data): string {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
  $remainder = strlen($data) % 4;
  if ($remainder) $data .= str_repeat('=', 4 - $remainder);
  return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_sign(array $payload, string $secret): string {
  $header = ['alg' => 'HS256', 'typ' => 'JWT'];
  $segments = [
    base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES)),
    base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES))
  ];
  $input = implode('.', $segments);
  $sig = hash_hmac('sha256', $input, $secret, true);
  $segments[] = base64url_encode($sig);
  return implode('.', $segments);
}

function jwt_verify(string $token, string $secret): array {
  $parts = explode('.', $token);
  if (count($parts) !== 3) throw new Exception('TOKEN_INVALID');

  [$h, $p, $s] = $parts;
  $input = $h . '.' . $p;
  $sig = base64url_decode($s);
  $expected = hash_hmac('sha256', $input, $secret, true);
  if (!hash_equals($expected, $sig)) throw new Exception('TOKEN_INVALID');

  $payload = json_decode(base64url_decode($p), true);
  if (!$payload) throw new Exception('TOKEN_INVALID');

  if (isset($payload['exp']) && time() > (int)$payload['exp']) {
    throw new Exception('TOKEN_EXPIRED');
  }

  return $payload;
}
```

#### /app/middleware/auth_middleware.php
```php
<?php
require_once __DIR__ . '/../services/Jwt.php';

function get_bearer_token(): ?string {
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!$hdr) return null;
  if (!preg_match('/Bearer\s+(.*)$/i', $hdr, $m)) return null;
  return trim($m[1]);
}

function require_auth(): array {
  $env = require __DIR__ . '/../config/env.php';
  $token = get_bearer_token();
  if (!$token) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => ['code' => 'UNAUTH', 'message' => 'Token ausente']]);
    exit;
  }

  try {
    return jwt_verify($token, $env['JWT_SECRET']);
  } catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => ['code' => $e->getMessage(), 'message' => 'Token inválido']]);
    exit;
  }
}

function require_admin(array $auth): void {
  if (($auth['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Acesso negado']]);
    exit;
  }
}
```

### 13.3 API (auth + utils + perfil)

#### /app/api/v1/auth.php
```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../services/Jwt.php';

cors();
$env = require __DIR__ . '/../../config/env.php';
$action = $_GET['action'] ?? '';

function json_input(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

if ($action === 'login') {
  $in = json_input();
  $email = strtolower(trim($in['email'] ?? ''));
  $pass  = (string)($in['password'] ?? '');

  if (!$email || !$pass) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Email e senha são obrigatórios']]);
    exit;
  }

  $pdo = db();
  $st = $pdo->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
  $st->execute([$email]);
  $u = $st->fetch();

  if (!$u || $u['status'] !== 'active' || !password_verify($pass, $u['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Credenciais inválidas']]);
    exit;
  }

  $now = time();
  $payload = [
    'sub' => (int)$u['id'],
    'name' => $u['name'],
    'email' => $u['email'],
    'role' => $u['role'],
    'iat' => $now,
    'exp' => $now + (int)$env['JWT_TTL_SECONDS'],
  ];

  $token = jwt_sign($payload, $env['JWT_SECRET']);
  echo json_encode(['ok' => true, 'data' => ['token' => $token]]);
  exit;
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Ação inválida']]);
```

#### /app/api/v1/utils.php (ViaCEP)
```php
<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';

cors();
require_auth();

$action = $_GET['action'] ?? '';

if ($action === 'viacep') {
  $cep = preg_replace('/\D/', '', (string)($_GET['cep'] ?? ''));
  if (strlen($cep) !== 8) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'CEP inválido']]);
    exit;
  }

  $json = @file_get_contents('https://viacep.com.br/ws/' . $cep . '/json/');
  if (!$json) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VIACEP_FAIL', 'message' => 'Falha ao consultar ViaCEP']]);
    exit;
  }

  $data = json_decode($json, true);
  if (!is_array($data) || isset($data['erro'])) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => ['code' => 'CEP_NOT_FOUND', 'message' => 'CEP não encontrado']]);
    exit;
  }

  echo json_encode(['ok' => true, 'data' => [
    'cep' => preg_replace('/\D/', '', $data['cep'] ?? ''),
    'logradouro' => $data['logradouro'] ?? null,
    'bairro' => $data['bairro'] ?? null,
    'cidade' => $data['localidade'] ?? null,
    'uf' => $data['uf'] ?? null,
  ]]);
  exit;
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Ação inválida']]);
```

#### /app/api/v1/members.php (perfil get/update)
```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';

cors();
$auth = require_auth();
$action = $_GET['action'] ?? '';

function json_input(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function member_by_user(PDO $pdo, int $userId): ?array {
  $st = $pdo->prepare('SELECT * FROM members WHERE user_id = ? LIMIT 1');
  $st->execute([$userId]);
  $m = $st->fetch();
  return $m ?: null;
}

if ($action === 'get') {
  $pdo = db();
  $m = member_by_user($pdo, (int)$auth['sub']);
  if (!$m) {
    echo json_encode(['ok' => true, 'data' => ['member' => null, 'address' => null]]);
    exit;
  }

  $st = $pdo->prepare('SELECT * FROM addresses WHERE member_id = ? LIMIT 1');
  $st->execute([(int)$m['id']]);
  $addr = $st->fetch() ?: null;

  echo json_encode(['ok' => true, 'data' => ['member' => $m, 'address' => $addr]]);
  exit;
}

if ($action === 'update') {
  $in = json_input();
  $pdo = db();
  $userId = (int)$auth['sub'];

  $cpf = preg_replace('/\D/', '', (string)($in['cpf'] ?? ''));
  if (strlen($cpf) !== 11) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'CPF inválido']]);
    exit;
  }

  $pdo->beginTransaction();
  try {
    $m = member_by_user($pdo, $userId);

    $nome = trim((string)($in['nome'] ?? ''));
    if (!$nome) throw new Exception('NOME_OBRIGATORIO');

    $categoria = ($in['categoria'] ?? 'PARCIAL') === 'INTEGRAL' ? 'INTEGRAL' : 'PARCIAL';
    $status = ($in['status'] ?? 'ATIVO') === 'INATIVO' ? 'INATIVO' : 'ATIVO';

    if ($m) {
      $st = $pdo->prepare('SELECT id FROM members WHERE cpf = ? AND user_id <> ? LIMIT 1');
      $st->execute([$cpf, $userId]);
      if ($st->fetch()) throw new Exception('CPF_DUPLICADO');

      $st = $pdo->prepare('UPDATE members SET nome=?, lotacao=?, cargo=?, cpf=?, data_filiacao=?, categoria=?, status=?, contribuicao_mensal=?, matricula=?, telefone=?, email_funcional=? WHERE user_id=?');
      $st->execute([
        $nome,
        ($in['lotacao'] ?? null) ?: null,
        ($in['cargo'] ?? null) ?: null,
        $cpf,
        ($in['data_filiacao'] ?? null) ?: null,
        $categoria,
        $status,
        isset($in['contribuicao_mensal']) ? (float)$in['contribuicao_mensal'] : null,
        ($in['matricula'] ?? null) ?: null,
        ($in['telefone'] ?? null) ?: null,
        ($in['email_funcional'] ?? null) ?: null,
        $userId
      ]);
      $memberId = (int)$m['id'];
    } else {
      $st = $pdo->prepare('INSERT INTO members (user_id, nome, lotacao, cargo, cpf, data_filiacao, categoria, status, contribuicao_mensal, matricula, telefone, email_funcional) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
      $st->execute([
        $userId,
        $nome,
        ($in['lotacao'] ?? null) ?: null,
        ($in['cargo'] ?? null) ?: null,
        $cpf,
        ($in['data_filiacao'] ?? null) ?: null,
        $categoria,
        $status,
        isset($in['contribuicao_mensal']) ? (float)$in['contribuicao_mensal'] : null,
        ($in['matricula'] ?? null) ?: null,
        ($in['telefone'] ?? null) ?: null,
        ($in['email_funcional'] ?? null) ?: null,
      ]);
      $memberId = (int)$pdo->lastInsertId();
    }

    $addr = $in['address'] ?? [];
    $cep = preg_replace('/\D/', '', (string)($addr['cep'] ?? ''));
    if ($cep && strlen($cep) !== 8) throw new Exception('CEP_INVALIDO');

    if ($cep) {
      $st = $pdo->prepare('SELECT id FROM addresses WHERE member_id = ? LIMIT 1');
      $st->execute([$memberId]);
      $exists = $st->fetch();

      $payload = [
        $cep,
        ($addr['logradouro'] ?? null) ?: null,
        ($addr['numero'] ?? null) ?: null,
        ($addr['complemento'] ?? null) ?: null,
        ($addr['bairro'] ?? null) ?: null,
        ($addr['cidade'] ?? null) ?: null,
        strtoupper(trim((string)($addr['uf'] ?? ''))) ?: null,
        $memberId
      ];

      if ($exists) {
        $st = $pdo->prepare('UPDATE addresses SET cep=?, logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, uf=? WHERE member_id=?');
        $st->execute($payload);
      } else {
        $st = $pdo->prepare('INSERT INTO addresses (cep, logradouro, numero, complemento, bairro, cidade, uf, member_id) VALUES (?,?,?,?,?,?,?,?)');
        $st->execute($payload);
      }
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'data' => ['member_id' => $memberId]]);
    exit;

  } catch (Exception $e) {
    $pdo->rollBack();

    $code = $e->getMessage();
    $msg = 'Falha ao salvar';
    if ($code === 'NOME_OBRIGATORIO') $msg = 'Nome é obrigatório';
    if ($code === 'CPF_DUPLICADO') $msg = 'CPF já cadastrado';
    if ($code === 'CEP_INVALIDO') $msg = 'CEP inválido';

    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => ['code' => $code, 'message' => $msg]]);
    exit;
  }
}

http_response_code(404);
echo json_encode(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Ação inválida']]);
```

### 13.4 Frontend — fetch

#### /public/assets/js/api.js
```js
export function getToken() {
  return localStorage.getItem('token') || '';
}

export function setToken(token) {
  localStorage.setItem('token', token);
}

export async function api(path, { method = 'GET', body = null } = {}) {
  const headers = { 'Content-Type': 'application/json' };
  const token = getToken();
  if (token) headers.Authorization = 'Bearer ' + token;

  const res = await fetch(path, {
    method,
    headers,
    body: body ? JSON.stringify(body) : null
  });

  const json = await res.json().catch(() => null);
  if (!res.ok) throw new Error(json?.error?.message || 'Erro na requisição');
  return json;
}
```

Inclua isso no login.php, dashboard.php e perfil.php.

Dica prática:
- Login salva token no localStorage
- Páginas privadas checam token e redirecionam se faltar
- API protege tudo com middleware
