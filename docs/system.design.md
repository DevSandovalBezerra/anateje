# ANATEJE Design System

Status: v1.1 (baseline para desenvolvimento)
Escopo: Site institucional + area do associado + painel admin

## 1) Objetivo

Este documento define as regras visuais e de interface do projeto ANATEJE.
Todo layout novo deve seguir este arquivo para manter consistencia, coesao e velocidade de entrega.

Regra de precedencia:
1. Manual de identidade visual (MIV)
2. Este `system.design.md`
3. Ajustes pontuais aprovados no projeto

Se houver conflito entre implementacao e este documento, este documento prevalece ate revisao formal.

## 2) Direcao de marca

Personalidade visual:
- Institucional, confiavel, solida
- Moderna sem perder seriedade
- Foco em conversao (CTA forte e recorrente)

Direcao estetica:
- Base institucional com suporte a dois temas:
  - `light` (obrigatoriamente claro)
  - `dark` (dark-blue / monokai)
- Gradientes em pontos de destaque
- Blocos amplos com cards
- Contraste alto e leitura facil

### 2.1 Regra obrigatoria de tema claro

Quando o tema ativo for `light`, os fundos de pagina devem ser realmente claros:
- Fundo principal claro (nao preto/cafe escuro)
- Superficies claras para cards e formularios
- Texto principal escuro para contraste
- Sidebar e header em tons claros coerentes com o restante da tela

Nao e permitido manter paleta escura no tema `light`.

## 3) Fundamentos oficiais (MIV/PRD)

Tipografia:
- Titulos: `League Spartan`
- Texto corrido: `Open Sans`

Paleta base:
- `--color-bg`: `#0d0702` (fundo principal)
- `--color-neutral`: `#696969` (neutro de apoio)
- `--color-wine`: `#5c2620` (secundaria institucional)
- `--color-gold`: `#a6764c` (destaques e acentos)
- `--color-blue`: `#262353` (apoio institucional)

## 4) Tokens de design

Todos os estilos devem usar tokens, nunca hex hardcoded em componentes.

### 4.1 Cores semanticas por tema

```css
:root[data-theme="light"] {
  --bg-primary: #f5f7fb;
  --bg-secondary: #eef3fa;
  --surface-1: #ffffff;
  --surface-2: #eef3fa;
  --surface-3: #e4ebf6;

  --text-primary: #1d2430;
  --text-secondary: #4d5b6d;
  --text-muted: #75849a;

  --border-subtle: #d4dce8;
  --border-strong: #bcc9dc;
}

:root[data-theme="dark-blue"] {
  --bg-primary: #0a1020;
  --bg-secondary: #11192b;
  --surface-1: #172238;
  --surface-2: #1f2c45;
  --surface-3: #243553;

  --text-primary: #edf2ff;
  --text-secondary: #c0cce7;
  --text-muted: #95a5c8;

  --brand-primary: #7ca1ff;
  --brand-secondary: #24315a;
  --brand-tertiary: #2e61d3;

  --border-subtle: #2a3e69;
  --border-strong: #46619c;

  --success: #1f8f5f;
  --warning: #b9862f;
  --danger: #b74242;
  --info: #3a69d8;
}
```

Observacao:
- `light` e o tema claro oficial e deve ser usado como referencia para contraste e legibilidade em ambiente administrativo.
- Temas escuros sao opcionais e nao podem contaminar os tokens do `light`.

### 4.2 Gradientes

```css
:root {
  --grad-hero: linear-gradient(135deg, #262353 0%, #5c2620 55%, #a6764c 100%);
  --grad-cta: linear-gradient(90deg, #a6764c 0%, #c08a5a 100%);
  --grad-card: linear-gradient(180deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.00) 100%);
}
```

Uso:
- `--grad-hero`: hero principal e faixas de impacto
- `--grad-cta`: botoes primarios e banners de acao
- `--grad-card`: overlays sutis em cards

### 4.3 Espacamento e raio

```css
:root {
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 24px;
  --space-6: 32px;
  --space-7: 48px;
  --space-8: 64px;

  --radius-sm: 8px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-xl: 24px;
}
```

### 4.4 Sombras

```css
:root {
  --shadow-sm: 0 2px 8px rgba(0,0,0,0.18);
  --shadow-md: 0 8px 24px rgba(0,0,0,0.24);
  --shadow-lg: 0 18px 40px rgba(0,0,0,0.30);
}
```

## 5) Tipografia

Stack:
- Heading: `"League Spartan", "Segoe UI", sans-serif`
- Body: `"Open Sans", "Segoe UI", sans-serif`

Escala:
- `h1`: 56/64, peso 700
- `h2`: 42/50, peso 700
- `h3`: 32/40, peso 600
- `h4`: 24/32, peso 600
- `body-lg`: 20/32, peso 400
- `body`: 16/26, peso 400
- `body-sm`: 14/22, peso 400
- `caption`: 12/18, peso 400

Regras:
- Evitar texto longo em caixa alta
- Titulos curtos e diretos
- Contraste minimo WCAG AA

## 6) Grid e responsividade

Breakpoints:
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px
- `2xl`: 1536px

Containers:
- Site publico: max 1200px
- Area logada/admin: max 1360px

Padrao de secao publica:
- Desktop: `padding-block: 96px`
- Tablet: `padding-block: 72px`
- Mobile: `padding-block: 56px`

## 7) Componentes base

### 7.1 Botao

Variantes:
- `btn-primary`: gradiente CTA, texto escuro, destaque principal
- `btn-secondary`: fundo `surface-2`, borda `border-strong`
- `btn-ghost`: transparente, foco em texto
- `btn-danger`: fundo `danger`

Estados:
- Default, Hover, Active, Disabled, Focus-visible
- Focus sempre com anel visivel de alto contraste

### 7.2 Card

Padrao:
- Fundo `surface-1`
- Borda `border-subtle`
- Radius `radius-lg`
- Sombra `shadow-sm`
- Hover opcional com leve elevacao (`shadow-md`)

### 7.3 Campos de formulario

Padrao:
- Altura minima 44px
- Label sempre visivel
- Placeholder com `text-muted`
- Erro abaixo do campo com texto objetivo

Formulario de filiacao deve seguir:
- Ordem dos campos definida no escopo de cadastro
- Mascaras para CPF, telefone e CEP
- Busca de endereco por CEP (ViaCEP)

### 7.4 Badge e status

Status de associado:
- Ativo: base `success`
- Inativo: base `danger`

Categoria:
- Parcial (0,5%)
- Integral (1%)

## 8) Layout por dominio

### 8.1 Site publico

Paginas:
- Home (landing)
- Sobre
- Beneficios
- Eventos
- Blog/Noticias
- Contato
- Filiacao

Regras:
- Navbar fixa no topo com CTA "Associe-se"
- Hero com CTA principal e CTA secundaria
- CTA repetida ao longo da pagina
- Rodape institucional em todas as paginas

### 8.2 Area do associado

Paginas:
- Dashboard
- Perfil
- Meus beneficios
- Meus eventos
- Comunicados

Regras:
- Navegacao clara por modulos
- Priorizar leitura de status, categoria e proximas acoes
- Evitar poluicao visual

### 8.3 Admin

Paginas:
- Dashboard admin
- Associados
- Beneficios
- Eventos
- Comunicados
- Campanhas
- Integracoes

Regras:
- Foco em produtividade
- Tabelas legiveis, filtros evidentes, acao primaria clara
- Confirmacao explicita para operacoes destrutivas

## 9) Iconografia e midia

Icones:
- Trazo simples, consistente
- Tamanho base: 16, 20, 24

Imagens:
- Sempre com fallback
- Preferir proporcoes consistentes por sessao (ex: 16:9 em cards de evento)

## 10) Movimento e microinteracao

Duracoes:
- Rapida: 120ms
- Media: 200ms
- Lenta: 300ms

Curvas:
- Entrada: `cubic-bezier(0.2, 0.8, 0.2, 1)`
- Saida: `ease-in`

Regras:
- Animacao deve comunicar estado
- Evitar animacao decorativa excessiva

## 11) Acessibilidade minima obrigatoria

- Contraste AA em texto e elementos interativos
- Navegacao completa por teclado
- `focus-visible` claro
- Labels em todos os campos
- Mensagens de erro objetivas e associadas ao campo
- Alvos de toque >= 44x44

## 12) Regras de implementacao

- Nao usar cor direta em componente: sempre token
- Nao criar variante visual sem registrar aqui
- Evitar estilos inline
- Manter consistencia entre publico, associado e admin
- Toda nova tela deve ter referencia de componentes deste documento

Arquivos recomendados:
- `assets/css/tokens.css` (tokens)
- `assets/css/components.css` (componentes)
- `assets/css/layout.css` (estrutura)
- `assets/css/pages/*.css` (ajustes locais)

## 13) Checklist de QA visual

Antes de aprovar uma tela:
1. Usa tipografia oficial?
2. Usa tokens oficiais?
3. Segue espacamento e grid?
4. CTAs estao consistentes?
5. Estados de hover/focus/disabled existem?
6. Mobile (320px+) esta funcional?
7. Contraste e foco atendem acessibilidade?
8. Tema `light` esta realmente claro (fundo e superficies)?

## 14) Governanca

Mudancas neste arquivo exigem:
1. Justificativa curta
2. Impacto em telas existentes
3. Atualizacao de componentes afetados

---

Este documento e a fonte de verdade para decisoes visuais do projeto ANATEJE.
