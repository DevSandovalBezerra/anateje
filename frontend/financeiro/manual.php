<?php
require_once __DIR__ . '/protect.php';
require_once __DIR__ . '/../../includes/base_path.php';
$baseUrl = lidergest_base_url();
?>
<style>
    .manual-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.5rem;
    }

    .manual-hero {
        border-radius: 14px;
        padding: 1.5rem;
        color: #fff;
        background: linear-gradient(135deg, #1f3a5f, #31598d);
        box-shadow: 0 10px 30px rgba(31, 58, 95, 0.25);
        margin-bottom: 1.5rem;
    }

    .manual-hero h1 {
        margin: 0;
        font-size: 1.9rem;
        font-weight: 700;
    }

    .manual-hero p {
        margin: 0.5rem 0 0;
        opacity: 0.95;
    }

    .manual-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
    }

    .manual-section h2 {
        margin: 0 0 0.75rem;
        font-size: 1.2rem;
        font-weight: 700;
        color: #111827;
    }

    .manual-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 0.75rem;
    }

    .manual-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.9rem;
        background: #f9fafb;
    }

    .manual-card h3 {
        margin: 0 0 0.4rem;
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
    }

    .manual-card p {
        margin: 0;
        color: #4b5563;
        font-size: 0.92rem;
    }

    .manual-list {
        margin: 0;
        padding-left: 1rem;
        color: #374151;
    }

    .manual-list li {
        margin-bottom: 0.45rem;
    }
</style>

<div class="cadastros-content">
    <div class="manual-wrapper">
        <div class="manual-hero">
            <h1>Manual Pratico do Financeiro ANATEJE</h1>
            <p>Guia objetivo para operacao financeira de uma associacao: entrada, saida, cobrancas, anuidade e analise.</p>
        </div>

        <section class="manual-section">
            <h2>Visao Geral</h2>
            <p class="text-secondary-dark-gray">
                O modulo financeiro ANATEJE foi organizado para rotina associativa. O foco e controlar caixa, obrigacoes,
                cobrancas de associados, contratos de servico e previsao de fluxo com rastreabilidade por categoria,
                centro de custo e responsavel.
            </p>
        </section>

        <section class="manual-section">
            <h2>1. Cadastros Estruturais</h2>
            <div class="manual-grid">
                <article class="manual-card">
                    <h3>Contas Bancarias</h3>
                    <p>Cadastro de contas correntes, caixas e carteiras para movimentacao oficial da associacao.</p>
                </article>
                <article class="manual-card">
                    <h3>Pessoas</h3>
                    <p>Cadastro de associados, fornecedores, colaboradores e contatos financeiros relacionados.</p>
                </article>
                <article class="manual-card">
                    <h3>Categorias Financeiras</h3>
                    <p>Padrao de classificacao para receitas e despesas com visao gerencial por natureza.</p>
                </article>
                <article class="manual-card">
                    <h3>Centros de Custo</h3>
                    <p>Separacao por nucleo, projeto, evento ou unidade para comparacao de desempenho.</p>
                </article>
                <article class="manual-card">
                    <h3>Receitas e Despesas Fixas</h3>
                    <p>Automacao de compromissos recorrentes para reduzir retrabalho e evitar esquecimento.</p>
                </article>
            </div>
        </section>

        <section class="manual-section">
            <h2>2. Operacao Diaria</h2>
            <div class="manual-grid">
                <article class="manual-card">
                    <h3>Lancamentos Financeiros</h3>
                    <p>Registro unificado de receitas, despesas e transferencias com status, vencimento e anexos.</p>
                </article>
                <article class="manual-card">
                    <h3>Pagamentos</h3>
                    <p>Conferencia de quitacoes e historico de pagamentos com filtros por status e periodo.</p>
                </article>
                <article class="manual-card">
                    <h3>Transferencias</h3>
                    <p>Movimentacoes internas entre contas para manter saldos corretos por origem e destino.</p>
                </article>
                <article class="manual-card">
                    <h3>Contas a Pagar e Receber</h3>
                    <p>Controle de parcelas, vencimentos, parcial/quitado e visao consolidada de pendencias.</p>
                </article>
            </div>
        </section>

        <section class="manual-section">
            <h2>3. Planejamento e Cobrancas</h2>
            <div class="manual-grid">
                <article class="manual-card">
                    <h3>Planos</h3>
                    <p>Definicao de planos de contribuicao e regras de valores para categorias de associados.</p>
                </article>
                <article class="manual-card">
                    <h3>Contratos</h3>
                    <p>Controle de vigencia, periodicidade e obrigacoes financeiras vinculadas.</p>
                </article>
                <article class="manual-card">
                    <h3>Cobrancas</h3>
                    <p>Geracao e acompanhamento de cobrancas com trilha de status e historico de acao.</p>
                </article>
                <article class="manual-card">
                    <h3>Renovacao de Filiacao</h3>
                    <p>Gestao da anuidade dos associados com padrao de status: Pendente, Renovado, Nao Renovado e Isento.</p>
                </article>
                <article class="manual-card">
                    <h3>Orcamentos</h3>
                    <p>Planejamento financeiro com comparativo entre previsto e realizado.</p>
                </article>
            </div>
        </section>

        <section class="manual-section">
            <h2>4. Controle Gerencial</h2>
            <div class="manual-grid">
                <article class="manual-card">
                    <h3>Dashboard Financeiro</h3>
                    <p>Indicadores consolidados para leitura rapida da saude financeira da associacao.</p>
                </article>
                <article class="manual-card">
                    <h3>Fluxo de Caixa</h3>
                    <p>Projecao de entradas e saidas para antecipar risco de liquidez.</p>
                </article>
                <article class="manual-card">
                    <h3>Conciliacao</h3>
                    <p>Conferencia de extrato versus sistema para fechar saldos e corrigir divergencias.</p>
                </article>
                <article class="manual-card">
                    <h3>Relatorios</h3>
                    <p>Exportacao e analise por periodo, categoria, centro de custo e inadimplencia.</p>
                </article>
            </div>
        </section>

        <section class="manual-section">
            <h2>Boas Praticas de Operacao</h2>
            <ul class="manual-list">
                <li>Padronize categorias antes de iniciar lancamentos em volume.</li>
                <li>Registre eventos financeiros no mesmo dia para manter indicadores confiaveis.</li>
                <li>Mantenha pessoa responsavel e centro de custo em todo lancamento relevante.</li>
                <li>Revise pendencias semanalmente e execute cobranca ativa de renovacoes atrasadas.</li>
                <li>Realize conciliacao periodica para evitar desvio entre saldo contabil e bancario.</li>
            </ul>
        </section>
    </div>
</div>

<script>
if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
}
</script>


