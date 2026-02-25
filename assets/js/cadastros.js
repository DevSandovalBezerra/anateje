// LiderGest - API de Cadastros
// Sistema de Gestão Pedagógico-Financeira Líder School

class CadastrosAPI {
    constructor() {
        this.baseUrl = getApiUrl('cadastros');
    }

    // ==============================================
    // UNIDADES
    // ==============================================

    async listarUnidades() {
        return await apiRequest('cadastros/unidades.php?action=listar');
    }

    async obterUnidade(id) {
        return await apiRequest(`cadastros/unidades.php?action=obter&id=${id}`);
    }

    async criarUnidade(dados) {
        const formData = new FormData();
        formData.append('action', 'criar');
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/unidades.php', {
            method: 'POST',
            body: formData
        });
    }

    async atualizarUnidade(id, dados) {
        const formData = new FormData();
        formData.append('action', 'atualizar');
        formData.append('id', id);
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/unidades.php', {
            method: 'POST',
            body: formData
        });
    }

    async excluirUnidade(id) {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        return await apiRequest('cadastros/unidades.php', {
            method: 'POST',
            body: formData
        });
    }

    async toggleStatusUnidade(id) {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', id);

        return await apiRequest('cadastros/unidades.php', {
            method: 'POST',
            body: formData
        });
    }

    // ==============================================
    // PROFESSORES
    // ==============================================

    async listarProfessores() {
        return await apiRequest('cadastros/professores.php?action=listar');
    }

    async obterProfessor(id) {
        return await apiRequest(`cadastros/professores.php?action=obter&id=${id}`);
    }

    async criarProfessor(dados) {
        const formData = new FormData();
        formData.append('action', 'criar');
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/professores.php', {
            method: 'POST',
            body: formData
        });
    }

    async atualizarProfessor(id, dados) {
        const formData = new FormData();
        formData.append('action', 'atualizar');
        formData.append('id', id);
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/professores.php', {
            method: 'POST',
            body: formData
        });
    }

    async excluirProfessor(id) {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        return await apiRequest('cadastros/professores.php', {
            method: 'POST',
            body: formData
        });
    }

    async toggleStatusProfessor(id) {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', id);

        return await apiRequest('cadastros/professores.php', {
            method: 'POST',
            body: formData
        });
    }

    async listarProfessoresPorUnidade(unidadeId) {
        return await apiRequest(`cadastros/professores.php?action=por_unidade&unidade_id=${unidadeId}`);
    }

    // ==============================================
    // TURMAS
    // ==============================================

    async listarTurmas() {
        return await apiRequest('cadastros/turmas.php?action=listar');
    }

    async obterTurma(id) {
        return await apiRequest(`cadastros/turmas.php?action=obter&id=${id}`);
    }

    async criarTurma(dados) {
        const formData = new FormData();
        formData.append('action', 'criar');
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/turmas.php', {
            method: 'POST',
            body: formData
        });
    }

    async atualizarTurma(id, dados) {
        const formData = new FormData();
        formData.append('action', 'atualizar');
        formData.append('id', id);
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/turmas.php', {
            method: 'POST',
            body: formData
        });
    }

    async excluirTurma(id) {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        return await apiRequest('cadastros/turmas.php', {
            method: 'POST',
            body: formData
        });
    }

    async alterarStatusTurma(id, status) {
        const formData = new FormData();
        formData.append('action', 'alterar_status');
        formData.append('id', id);
        formData.append('status', status);

        return await apiRequest('cadastros/turmas.php', {
            method: 'POST',
            body: formData
        });
    }

    async listarTurmasPorUnidade(unidadeId) {
        return await apiRequest(`cadastros/turmas.php?action=por_unidade&unidade_id=${unidadeId}`);
    }

    async obterEstatisticasTurma(id) {
        return await apiRequest(`cadastros/turmas.php?action=estatisticas&id=${id}`);
    }

    // ==============================================
    // SALAS
    // ==============================================

    async listarSalas() {
        return await apiRequest('cadastros/salas.php?action=listar');
    }

    async obterSala(id) {
        return await apiRequest(`cadastros/salas.php?action=obter&id=${id}`);
    }

    async criarSala(dados) {
        const formData = new FormData();
        formData.append('action', 'criar');
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/salas.php', {
            method: 'POST',
            body: formData
        });
    }

    async atualizarSala(id, dados) {
        const formData = new FormData();
        formData.append('action', 'atualizar');
        formData.append('id', id);
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/salas.php', {
            method: 'POST',
            body: formData
        });
    }

    async excluirSala(id) {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        return await apiRequest('cadastros/salas.php', {
            method: 'POST',
            body: formData
        });
    }

    async toggleStatusSala(id) {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', id);

        return await apiRequest('cadastros/salas.php', {
            method: 'POST',
            body: formData
        });
    }

    // ==============================================
    // ALUNOS
    // ==============================================

    async listarAlunos() {
        return await apiRequest('cadastros/alunos.php?action=listar');
    }

    async obterAluno(id) {
        return await apiRequest(`cadastros/alunos.php?action=obter&id=${id}`);
    }

    async criarAluno(dados) {
        const formData = new FormData();
        formData.append('action', 'criar');
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/alunos.php', {
            method: 'POST',
            body: formData
        });
    }

    async atualizarAluno(id, dados) {
        const formData = new FormData();
        formData.append('action', 'atualizar');
        formData.append('id', id);
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/alunos.php', {
            method: 'POST',
            body: formData
        });
    }

    async excluirAluno(id) {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        return await apiRequest('cadastros/alunos.php', {
            method: 'POST',
            body: formData
        });
    }

    async alterarStatusAluno(id, status) {
        const formData = new FormData();
        formData.append('action', 'alterar_status');
        formData.append('id', id);
        formData.append('status', status);

        return await apiRequest('cadastros/alunos.php', {
            method: 'POST',
            body: formData
        });
    }

    async listarAlunosPorTurma(turmaId) {
        return await apiRequest(`cadastros/alunos.php?action=por_turma&turma_id=${turmaId}`);
    }

    async vincularResponsavel(alunoId, responsavelId, tipoRelacionamento = '', principal = false) {
        const formData = new FormData();
        formData.append('action', 'vincular_responsavel');
        formData.append('aluno_id', alunoId);
        formData.append('responsavel_id', responsavelId);
        formData.append('tipo_relacionamento', tipoRelacionamento);
        formData.append('principal', principal ? 1 : 0);

        return await apiRequest('cadastros/alunos.php', {
            method: 'POST',
            body: formData
        });
    }

    async desvincularResponsavel(alunoId, responsavelId) {
        const formData = new FormData();
        formData.append('action', 'desvincular_responsavel');
        formData.append('aluno_id', alunoId);
        formData.append('responsavel_id', responsavelId);

        return await apiRequest('cadastros/alunos.php', {
            method: 'POST',
            body: formData
        });
    }

    // ==============================================
    // RESPONSÁVEIS
    // ==============================================

    async listarResponsaveis() {
        return await apiRequest('cadastros/responsaveis.php?action=listar');
    }

    async obterResponsavel(id) {
        return await apiRequest(`cadastros/responsaveis.php?action=obter&id=${id}`);
    }

    async criarResponsavel(dados) {
        const formData = new FormData();
        formData.append('action', 'criar');
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/responsaveis.php', {
            method: 'POST',
            body: formData
        });
    }

    async atualizarResponsavel(id, dados) {
        const formData = new FormData();
        formData.append('action', 'atualizar');
        formData.append('id', id);
        Object.keys(dados).forEach(key => {
            formData.append(key, dados[key]);
        });

        return await apiRequest('cadastros/responsaveis.php', {
            method: 'POST',
            body: formData
        });
    }

    async excluirResponsavel(id) {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);

        return await apiRequest('cadastros/responsaveis.php', {
            method: 'POST',
            body: formData
        });
    }

    async toggleStatusResponsavel(id) {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', id);

        return await apiRequest('cadastros/responsaveis.php', {
            method: 'POST',
            body: formData
        });
    }

    async listarResponsaveisPorTipo(tipo) {
        return await apiRequest(`cadastros/responsaveis.php?action=por_tipo&tipo=${tipo}`);
    }

    async buscarResponsaveis(termo) {
        return await apiRequest(`cadastros/responsaveis.php?action=buscar&termo=${encodeURIComponent(termo)}`);
    }

    async criarUsuarioResponsavel(responsavelId, email, senha) {
        const formData = new FormData();
        formData.append('action', 'criar_usuario');
        formData.append('responsavel_id', responsavelId);
        formData.append('email', email);
        formData.append('senha', senha);

        return await apiRequest('cadastros/responsaveis.php', {
            method: 'POST',
            body: formData
        });
    }
}

// Instância global
const cadastrosAPI = new CadastrosAPI();

// Exportar para uso global
window.cadastrosAPI = cadastrosAPI;