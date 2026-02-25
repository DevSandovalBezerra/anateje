// LiderGest - JavaScript para Módulo Financeiro
// Sistema de Gestão Pedagógico-Financeira Líder School

class FinanceiroAPI {
    constructor() {
        this.baseUrl = ApiConfig.getBaseUrl();
    }

    // ==================== PLANOS FINANCEIROS ====================

    // Listar todos os planos financeiros
    async listarPlanos() {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php?action=listar`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar planos:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter plano por ID
    async obterPlano(id) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php?action=obter&id=${id}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter plano:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Criar plano financeiro
    async criarPlano(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'criar');
            formData.append('nome', dados.nome);
            formData.append('descricao', dados.descricao || '');
            formData.append('valor_mensal', dados.valor_mensal);
            formData.append('valor_matricula', dados.valor_matricula || 0);
            formData.append('desconto_matricula', dados.desconto_matricula || 0);
            formData.append('desconto_mensalidade', dados.desconto_mensalidade || 0);
            formData.append('ativo', dados.ativo !== undefined ? dados.ativo : 1);

            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao criar plano:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Atualizar plano financeiro
    async atualizarPlano(id, dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'atualizar');
            formData.append('id', id);
            if (dados.nome !== undefined) formData.append('nome', dados.nome);
            if (dados.descricao !== undefined) formData.append('descricao', dados.descricao);
            if (dados.valor_mensal !== undefined) formData.append('valor_mensal', dados.valor_mensal);
            if (dados.valor_matricula !== undefined) formData.append('valor_matricula', dados.valor_matricula);
            if (dados.desconto_matricula !== undefined) formData.append('desconto_matricula', dados.desconto_matricula);
            if (dados.desconto_mensalidade !== undefined) formData.append('desconto_mensalidade', dados.desconto_mensalidade);
            if (dados.ativo !== undefined) formData.append('ativo', dados.ativo);

            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao atualizar plano:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Excluir plano financeiro
    async excluirPlano(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'excluir');
            formData.append('id', id);

            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao excluir plano:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Duplicar plano financeiro
    async duplicarPlano(id, novoNome) {
        try {
            const formData = new FormData();
            formData.append('action', 'duplicar');
            formData.append('id', id);
            formData.append('novo_nome', novoNome);

            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao duplicar plano:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Ativar/Desativar plano
    async toggleStatusPlano(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', id);

            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao alterar status do plano:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Calcular valores com desconto
    async calcularValoresComDesconto(planoId, valorBase = null) {
        try {
            let url = `${this.baseUrl}/api/financeiro/planos.php?action=calcular_desconto&id=${planoId}`;
            if (valorBase) url += `&valor_base=${valorBase}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao calcular valores:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter estatísticas dos planos
    async obterEstatisticasPlanos() {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/planos.php?action=estatisticas`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter estatísticas dos planos:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // ==================== CONTRATOS ====================

    // Listar contratos
    async listarContratos(filtros = {}) {
        try {
            let url = `${this.baseUrl}/api/financeiro/contratos.php?action=listar`;
            const params = new URLSearchParams();

            Object.keys(filtros).forEach(key => {
                if (filtros[key]) params.append(key, filtros[key]);
            });

            if (params.toString()) url += `&${params.toString()}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar contratos:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter contrato por ID
    async obterContrato(id) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/contratos.php?action=obter&id=${id}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter contrato:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Criar contrato
    async criarContrato(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'criar');
            formData.append('aluno_id', dados.aluno_id);
            formData.append('plano_financeiro_id', dados.plano_financeiro_id);
            formData.append('data_inicio', dados.data_inicio || new Date().toISOString().split('T')[0]);
            formData.append('data_fim', dados.data_fim || '');
            formData.append('status', dados.status || 'ativo');
            formData.append('observacoes', dados.observacoes || '');

            const response = await fetch(`${this.baseUrl}/api/financeiro/contratos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao criar contrato:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Atualizar contrato
    async atualizarContrato(id, dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'atualizar');
            formData.append('id', id);
            if (dados.data_inicio !== undefined) formData.append('data_inicio', dados.data_inicio);
            if (dados.data_fim !== undefined) formData.append('data_fim', dados.data_fim);
            if (dados.valor_mensal !== undefined) formData.append('valor_mensal', dados.valor_mensal);
            if (dados.valor_matricula !== undefined) formData.append('valor_matricula', dados.valor_matricula);
            if (dados.status !== undefined) formData.append('status', dados.status);
            if (dados.observacoes !== undefined) formData.append('observacoes', dados.observacoes);

            const response = await fetch(`${this.baseUrl}/api/financeiro/contratos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao atualizar contrato:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Cancelar contrato
    async cancelarContrato(id, motivo) {
        try {
            const formData = new FormData();
            formData.append('action', 'cancelar');
            formData.append('id', id);
            formData.append('motivo', motivo);

            const response = await fetch(`${this.baseUrl}/api/financeiro/contratos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao cancelar contrato:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Renovar contrato
    async renovarContrato(id, novaDataFim) {
        try {
            const formData = new FormData();
            formData.append('action', 'renovar');
            formData.append('id', id);
            formData.append('nova_data_fim', novaDataFim);

            const response = await fetch(`${this.baseUrl}/api/financeiro/contratos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao renovar contrato:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter estatísticas dos contratos
    async obterEstatisticasContratos(dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/financeiro/contratos.php?action=estatisticas`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter estatísticas dos contratos:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter contratos próximos ao vencimento
    async obterContratosProximosVencimento(dias = 30) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/contratos.php?action=proximos_vencimento&dias=${dias}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter contratos próximos ao vencimento:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // ==================== COBRANÇAS ====================

    // Listar cobranças
    async listarCobrancas(filtros = {}) {
        try {
            let url = `${this.baseUrl}/api/financeiro/cobrancas.php?action=listar`;
            const params = new URLSearchParams();

            Object.keys(filtros).forEach(key => {
                if (filtros[key]) params.append(key, filtros[key]);
            });

            if (params.toString()) url += `&${params.toString()}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar cobranças:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter cobrança por ID
    async obterCobranca(id) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php?action=obter&id=${id}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter cobrança:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Criar cobrança
    async criarCobranca(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'criar');
            formData.append('aluno_id', dados.aluno_id);
            formData.append('tipo', dados.tipo);
            formData.append('valor', dados.valor);
            formData.append('data_vencimento', dados.data_vencimento || new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
            formData.append('status', dados.status || 'pendente');
            formData.append('descricao', dados.descricao || '');
            formData.append('observacoes', dados.observacoes || '');

            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao criar cobrança:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Atualizar cobrança
    async atualizarCobranca(id, dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'atualizar');
            formData.append('id', id);
            if (dados.valor !== undefined) formData.append('valor', dados.valor);
            if (dados.data_vencimento !== undefined) formData.append('data_vencimento', dados.data_vencimento);
            if (dados.status !== undefined) formData.append('status', dados.status);
            if (dados.descricao !== undefined) formData.append('descricao', dados.descricao);
            if (dados.observacoes !== undefined) formData.append('observacoes', dados.observacoes);

            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao atualizar cobrança:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Cancelar cobrança
    async cancelarCobranca(id, motivo) {
        try {
            const formData = new FormData();
            formData.append('action', 'cancelar');
            formData.append('id', id);
            formData.append('motivo', motivo);

            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao cancelar cobrança:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Gerar cobranças em lote
    async gerarCobrancasLote(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'gerar_lote');
            formData.append('tipo', dados.tipo);
            formData.append('data_vencimento', dados.data_vencimento);
            formData.append('alunos', JSON.stringify(dados.alunos));
            formData.append('valor_padrao', dados.valor_padrao || 0);
            formData.append('descricao', dados.descricao || '');
            formData.append('observacoes', dados.observacoes || '');

            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao gerar cobranças em lote:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter estatísticas das cobranças
    async obterEstatisticasCobrancas(dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/financeiro/cobrancas.php?action=estatisticas`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter estatísticas das cobranças:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter cobranças vencidas
    async obterCobrancasVencidas(diasAtraso = 0) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php?action=vencidas&dias_atraso=${diasAtraso}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter cobranças vencidas:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter cobranças próximas ao vencimento
    async obterCobrancasProximasVencimento(dias = 5) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php?action=proximas_vencimento&dias=${dias}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter cobranças próximas ao vencimento:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Enviar lembretes de cobrança
    async enviarLembretes(cobrancaIds) {
        try {
            const formData = new FormData();
            formData.append('action', 'enviar_lembretes');
            formData.append('cobranca_ids', JSON.stringify(cobrancaIds));

            const response = await fetch(`${this.baseUrl}/api/financeiro/cobrancas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao enviar lembretes:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // ==================== PAGAMENTOS ====================

    // Listar pagamentos
    async listarPagamentos(filtros = {}) {
        try {
            let url = `${this.baseUrl}/api/financeiro/pagamentos.php?action=listar`;
            const params = new URLSearchParams();

            Object.keys(filtros).forEach(key => {
                if (filtros[key]) params.append(key, filtros[key]);
            });

            if (params.toString()) url += `&${params.toString()}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar pagamentos:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter pagamento por ID
    async obterPagamento(id) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/pagamentos.php?action=obter&id=${id}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter pagamento:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Registrar pagamento
    async registrarPagamento(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'registrar');
            formData.append('aluno_id', dados.aluno_id);
            if (dados.cobranca_id) formData.append('cobranca_id', dados.cobranca_id);
            formData.append('valor_pago', dados.valor_pago);
            formData.append('data_pagamento', dados.data_pagamento || new Date().toISOString().split('T')[0]);
            formData.append('forma_pagamento', dados.forma_pagamento);
            formData.append('status', dados.status || 'confirmado');
            if (dados.comprovante_url) formData.append('comprovante_url', dados.comprovante_url);
            formData.append('observacoes', dados.observacoes || '');

            const response = await fetch(`${this.baseUrl}/api/financeiro/pagamentos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao registrar pagamento:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Atualizar pagamento
    async atualizarPagamento(id, dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'atualizar');
            formData.append('id', id);
            if (dados.valor_pago !== undefined) formData.append('valor_pago', dados.valor_pago);
            if (dados.data_pagamento !== undefined) formData.append('data_pagamento', dados.data_pagamento);
            if (dados.forma_pagamento !== undefined) formData.append('forma_pagamento', dados.forma_pagamento);
            if (dados.status !== undefined) formData.append('status', dados.status);
            if (dados.comprovante_url !== undefined) formData.append('comprovante_url', dados.comprovante_url);
            if (dados.observacoes !== undefined) formData.append('observacoes', dados.observacoes);

            const response = await fetch(`${this.baseUrl}/api/financeiro/pagamentos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao atualizar pagamento:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Cancelar pagamento
    async cancelarPagamento(id, motivo) {
        try {
            const formData = new FormData();
            formData.append('action', 'cancelar');
            formData.append('id', id);
            formData.append('motivo', motivo);

            const response = await fetch(`${this.baseUrl}/api/financeiro/pagamentos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao cancelar pagamento:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter estatísticas dos pagamentos
    async obterEstatisticasPagamentos(dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/financeiro/pagamentos.php?action=estatisticas`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter estatísticas dos pagamentos:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter pagamentos por período
    async obterPagamentosPorPeriodo(dataInicio, dataFim) {
        try {
            const response = await fetch(`${this.baseUrl}/api/financeiro/pagamentos.php?action=por_periodo&data_inicio=${dataInicio}&data_fim=${dataFim}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter pagamentos por período:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }

    // Obter relatório de inadimplência
    async obterRelatorioInadimplencia(dataReferencia = null) {
        try {
            let url = `${this.baseUrl}/api/financeiro/pagamentos.php?action=inadimplencia`;
            if (dataReferencia) url += `&data_referencia=${dataReferencia}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter relatório de inadimplência:', error);
            return {
                success: false,
                message: 'Erro de conexão'
            };
        }
    }
}

// Instanciar API global
const financeiroAPI = new FinanceiroAPI();

// Funções utilitárias para o módulo financeiro
class FinanceiroUtils {
    // Formatar valor monetário
    static formatarMoeda(valor) {
        if (!valor) return 'R$ 0,00';
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    // Formatar data para exibição
    static formatarData(data) {
        if (!data) return '';
        const date = new Date(data);
        return date.toLocaleDateString('pt-BR');
    }

    // Obter cor por status de cobrança
    static obterCorStatusCobranca(status) {
        switch (status) {
            case 'pendente':
                return 'text-yellow-600 bg-yellow-100';
            case 'paga':
                return 'text-green-600 bg-green-100';
            case 'cancelada':
                return 'text-red-600 bg-red-100';
            case 'vencida':
                return 'text-red-600 bg-red-100';
            case 'proximo_vencimento':
                return 'text-orange-600 bg-orange-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    }

    // Obter cor por status de pagamento
    static obterCorStatusPagamento(status) {
        switch (status) {
            case 'confirmado':
                return 'text-green-600 bg-green-100';
            case 'pendente':
                return 'text-yellow-600 bg-yellow-100';
            case 'cancelado':
                return 'text-red-600 bg-red-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    }

    // Obter cor por status de contrato
    static obterCorStatusContrato(status) {
        switch (status) {
            case 'ativo':
                return 'text-green-600 bg-green-100';
            case 'finalizado':
                return 'text-blue-600 bg-blue-100';
            case 'cancelado':
                return 'text-red-600 bg-red-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    }

    // Calcular dias até vencimento
    static calcularDiasVencimento(dataVencimento) {
        if (!dataVencimento) return 0;
        const hoje = new Date();
        const vencimento = new Date(dataVencimento);
        const diffTime = vencimento - hoje;
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    // Obter ícone por forma de pagamento
    static obterIconeFormaPagamento(forma) {
        switch (forma) {
            case 'dinheiro':
                return 'banknote';
            case 'pix':
                return 'smartphone';
            case 'cartao':
                return 'credit-card';
            case 'transferencia':
                return 'arrow-right-left';
            default:
                return 'dollar-sign';
        }
    }

    // Validar CPF
    static validarCPF(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');

        if (cpf.length !== 11) return false;

        // Verificar se todos os dígitos são iguais
        if (/^(\d)\1{10}$/.test(cpf)) return false;

        // Validar dígitos verificadores
        let soma = 0;
        for (let i = 0; i < 9; i++) {
            soma += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let resto = 11 - (soma % 11);
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(9))) return false;

        soma = 0;
        for (let i = 0; i < 10; i++) {
            soma += parseInt(cpf.charAt(i)) * (11 - i);
        }
        resto = 11 - (soma % 11);
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.charAt(10))) return false;

        return true;
    }

    // Aplicar máscara de CPF
    static aplicarMascaraCPF(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    // Gerar relatório financeiro em CSV
    static gerarRelatorioFinanceiroCSV(dados, nomeArquivo = 'relatorio_financeiro.csv') {
        const headers = ['Data', 'Aluno', 'Tipo', 'Valor', 'Status', 'Forma Pagamento'];
        const csvContent = [
            headers.join(','),
            ...dados.map(item => [
                item.data_pagamento || item.data_vencimento,
                `"${item.aluno_nome}"`,
                item.tipo || item.tipo_cobranca,
                item.valor_pago || item.valor,
                item.status,
                item.forma_pagamento || '-'
            ].join(','))
        ].join('\n');

        const blob = new Blob([csvContent], {
            type: 'text/csv;charset=utf-8;'
        });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', nomeArquivo);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Calcular percentual de inadimplência
    static calcularPercentualInadimplencia(totalCobrancas, cobrancasVencidas) {
        if (totalCobrancas === 0) return 0;
        return Math.round((cobrancasVencidas / totalCobrancas) * 100);
    }

    // Obter status de cobrança detalhado
    static obterStatusDetalhado(dataVencimento, status) {
        if (status !== 'pendente') return status;

        const dias = this.calcularDiasVencimento(dataVencimento);

        if (dias < 0) return 'vencida';
        if (dias <= 5) return 'proximo_vencimento';
        return 'pendente';
    }
}