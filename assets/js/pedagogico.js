// LiderGest - JavaScript para MÃ³dulo PedagÃ³gico
// Sistema de GestÃ£o PedagÃ³gico-Financeira LÃ­der School

class PedagogicoAPI {
    constructor() {
        if (typeof apiConfig !== 'undefined') {
            this.baseUrl = apiConfig.baseUrl || apiConfig.getBaseUrl();
        } else if (typeof ApiConfig !== 'undefined') {
            const tempConfig = new ApiConfig();
            this.baseUrl = tempConfig.baseUrl || tempConfig.getBaseUrl();
        } else {
            console.warn('[PedagogicoAPI] ApiConfig nÃ£o encontrado, usando "/lidergest" como fallback');
            this.baseUrl = '/lidergest';
        }
    }

    // ==================== FREQUÃŠNCIA ====================

    // Listar frequÃªncia por turma e data
    async listarFrequencia(turmaId, data) {
        try {
            const response = await fetch(`${this.baseUrl}/api/pedagogico/frequencia.php?action=listar&turma_id=${turmaId}&data=${data}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar frequÃªncia:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Registrar frequÃªncia
    async registrarFrequencia(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'registrar');
            formData.append('turma_id', dados.turma_id);
            formData.append('data_aula', dados.data_aula);
            formData.append('professor_id', dados.professor_id);
            formData.append('registros', JSON.stringify(dados.registros));

            const response = await fetch(`${this.baseUrl}/api/pedagogico/frequencia.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao registrar frequÃªncia:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Listar alunos da turma para frequÃªncia
    async listarAlunosTurma(turmaId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/pedagogico/frequencia.php?action=alunos_turma&turma_id=${turmaId}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar alunos da turma:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Obter frequÃªncia de um aluno
    async obterFrequenciaAluno(alunoId, dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/pedagogico/frequencia.php?action=frequencia_aluno&aluno_id=${alunoId}`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter frequÃªncia do aluno:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Obter estatÃ­sticas de frequÃªncia da turma
    async obterEstatisticasFrequencia(turmaId, dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/pedagogico/frequencia.php?action=estatisticas_turma&turma_id=${turmaId}`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter estatÃ­sticas de frequÃªncia:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Justificar falta
    async justificarFalta(frequenciaId, observacoes) {
        try {
            const formData = new FormData();
            formData.append('action', 'justificar_falta');
            formData.append('frequencia_id', frequenciaId);
            formData.append('observacoes', observacoes);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/frequencia.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao justificar falta:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Marcar presenÃ§a em massa
    async marcarPresencaMassa(turmaId, dataAula, alunoIds, professorId) {
        try {
            const formData = new FormData();
            formData.append('action', 'marcar_presenca_massa');
            formData.append('turma_id', turmaId);
            formData.append('data_aula', dataAula);
            formData.append('aluno_ids', JSON.stringify(alunoIds));
            formData.append('professor_id', professorId);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/frequencia.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao marcar presenÃ§a em massa:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // ==================== NOTAS ====================

    // Listar notas por turma
    async listarNotas(turmaId, planoAulaId = null) {
        try {
            let url = `${this.baseUrl}/api/pedagogico/notas.php?action=listar&turma_id=${turmaId}`;
            if (planoAulaId) url += `&plano_aula_id=${planoAulaId}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar notas:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Criar nota
    async criarNota(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'criar');
            formData.append('aluno_id', dados.aluno_id);
            formData.append('turma_id', dados.turma_id);
            formData.append('professor_id', dados.professor_id);
            formData.append('tipo_avaliacao', dados.tipo_avaliacao || '');
            formData.append('nota', dados.nota || '');
            formData.append('competencia', dados.competencia || '');
            formData.append('observacoes', dados.observacoes || '');
            formData.append('data_avaliacao', dados.data_avaliacao || new Date().toISOString().split('T')[0]);
            if (dados.plano_aula_id) formData.append('plano_aula_id', dados.plano_aula_id);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/notas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao criar nota:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Atualizar nota
    async atualizarNota(id, dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'atualizar');
            formData.append('id', id);
            if (dados.tipo_avaliacao !== undefined) formData.append('tipo_avaliacao', dados.tipo_avaliacao);
            if (dados.nota !== undefined) formData.append('nota', dados.nota);
            if (dados.competencia !== undefined) formData.append('competencia', dados.competencia);
            if (dados.observacoes !== undefined) formData.append('observacoes', dados.observacoes);
            if (dados.data_avaliacao !== undefined) formData.append('data_avaliacao', dados.data_avaliacao);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/notas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao atualizar nota:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Excluir nota
    async excluirNota(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'excluir');
            formData.append('id', id);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/notas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao excluir nota:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Obter notas de um aluno
    async obterNotasAluno(alunoId, turmaId = null, dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/pedagogico/notas.php?action=notas_aluno&aluno_id=${alunoId}`;
            if (turmaId) url += `&turma_id=${turmaId}`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter notas do aluno:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Registrar notas em lote
    async registrarNotasLote(dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'registrar_lote');
            formData.append('turma_id', dados.turma_id);
            formData.append('professor_id', dados.professor_id);
            formData.append('data_avaliacao', dados.data_avaliacao || new Date().toISOString().split('T')[0]);
            formData.append('tipo_avaliacao', dados.tipo_avaliacao || '');
            formData.append('competencia', dados.competencia || '');
            formData.append('notas', JSON.stringify(dados.notas));

            const response = await fetch(`${this.baseUrl}/api/pedagogico/notas.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao registrar notas em lote:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Obter boletim de um aluno
    async obterBoletim(alunoId, turmaId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/pedagogico/notas.php?action=boletim&aluno_id=${alunoId}&turma_id=${turmaId}`, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter boletim:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // ==================== ANEXOS ====================

    // Listar anexos
    async listarAnexos(turmaId = null, planoAulaId = null, alunoId = null) {
        try {
            let url = `${this.baseUrl}/api/pedagogico/anexos.php?action=listar`;
            if (turmaId) url += `&turma_id=${turmaId}`;
            if (planoAulaId) url += `&plano_aula_id=${planoAulaId}`;
            if (alunoId) url += `&aluno_id=${alunoId}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao listar anexos:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Upload de arquivo
    async uploadAnexo(arquivo, dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'upload');
            formData.append('arquivo', arquivo);
            formData.append('titulo', dados.titulo || '');
            formData.append('descricao', dados.descricao || '');
            if (dados.turma_id) formData.append('turma_id', dados.turma_id);
            if (dados.plano_aula_id) formData.append('plano_aula_id', dados.plano_aula_id);
            if (dados.aluno_id) formData.append('aluno_id', dados.aluno_id);
            if (dados.professor_id) formData.append('professor_id', dados.professor_id);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/anexos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao fazer upload:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Atualizar anexo
    async atualizarAnexo(id, dados) {
        try {
            const formData = new FormData();
            formData.append('action', 'atualizar');
            formData.append('id', id);
            if (dados.titulo !== undefined) formData.append('titulo', dados.titulo);
            if (dados.descricao !== undefined) formData.append('descricao', dados.descricao);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/anexos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao atualizar anexo:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Excluir anexo
    async excluirAnexo(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'excluir');
            formData.append('id', id);

            const response = await fetch(`${this.baseUrl}/api/pedagogico/anexos.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao excluir anexo:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Download de arquivo
    downloadAnexo(id) {
        window.open(`${this.baseUrl}/api/pedagogico/anexos.php?action=download&id=${id}`, '_blank');
    }

    // Obter estatÃ­sticas de anexos
    async obterEstatisticasAnexos(turmaId = null, dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/pedagogico/anexos.php?action=estatisticas`;
            if (turmaId) url += `&turma_id=${turmaId}`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter estatÃ­sticas de anexos:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }

    // Obter anexos de um aluno
    async obterAnexosAluno(alunoId, dataInicio = null, dataFim = null) {
        try {
            let url = `${this.baseUrl}/api/pedagogico/anexos.php?action=anexos_aluno&aluno_id=${alunoId}`;
            if (dataInicio) url += `&data_inicio=${dataInicio}`;
            if (dataFim) url += `&data_fim=${dataFim}`;

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'include'
            });
            return await response.json();
        } catch (error) {
            console.error('Erro ao obter anexos do aluno:', error);
            return { success: false, message: 'Erro de conexÃ£o' };
        }
    }
}

// Instanciar API global
const pedagogicoAPI = new PedagogicoAPI();

// FunÃ§Ãµes utilitÃ¡rias para o mÃ³dulo pedagÃ³gico
class PedagogicoUtils {
    // Formatar data para exibiÃ§Ã£o
    static formatarData(data) {
        if (!data) return '';
        const date = new Date(data);
        return date.toLocaleDateString('pt-BR');
    }

    // Formatar tamanho de arquivo
    static formatarTamanhoArquivo(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Obter Ã­cone por tipo de arquivo
    static obterIconeTipoArquivo(tipo) {
        if (tipo.startsWith('image/')) return 'image';
        if (tipo === 'application/pdf') return 'file-text';
        if (tipo.includes('word')) return 'file-text';
        if (tipo.includes('excel') || tipo.includes('spreadsheet')) return 'table';
        if (tipo === 'text/plain') return 'file-text';
        return 'file';
    }

    // Calcular percentual de presenÃ§a
    static calcularPercentualPresenca(presencas, total) {
        if (total === 0) return 0;
        return Math.round((presencas / total) * 100);
    }

    // Obter cor por status de frequÃªncia
    static obterCorStatusFrequencia(status) {
        switch (status) {
            case 'presente': return 'text-green-600 bg-green-100';
            case 'falta': return 'text-red-600 bg-red-100';
            case 'falta_justificada': return 'text-yellow-600 bg-yellow-100';
            default: return 'text-gray-600 bg-gray-100';
        }
    }

    // Obter cor por nota
    static obterCorNota(nota) {
        if (nota >= 7) return 'text-green-600 bg-green-100';
        if (nota >= 5) return 'text-yellow-600 bg-yellow-100';
        return 'text-red-600 bg-red-100';
    }

    // Validar arquivo para upload
    static validarArquivo(arquivo) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];

        if (arquivo.size > maxSize) {
            return { valido: false, mensagem: 'Arquivo muito grande. MÃ¡ximo 10MB' };
        }

        if (!allowedTypes.includes(arquivo.type)) {
            return { valido: false, mensagem: 'Tipo de arquivo nÃ£o permitido' };
        }

        return { valido: true };
    }

    // Gerar relatÃ³rio de frequÃªncia em CSV
    static gerarRelatorioFrequenciaCSV(dados, nomeArquivo = 'relatorio_frequencia.csv') {
        const headers = ['Data', 'Turma', 'Unidade', 'Total Alunos', 'PresenÃ§as', 'Faltas', 'Faltas Justificadas', '% PresenÃ§a'];
        const csvContent = [
            headers.join(','),
            ...dados.map(item => [
                item.data_aula,
                `"${item.turma_nome}"`,
                `"${item.unidade_nome}"`,
                item.total_alunos,
                item.presencas,
                item.faltas,
                item.faltas_justificadas,
                item.percentual_presenca
            ].join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', nomeArquivo);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Gerar boletim em PDF (simulaÃ§Ã£o)
    static gerarBoletimPDF(alunoNome, dados) {
        // Esta funÃ§Ã£o seria implementada com uma biblioteca como jsPDF
        console.log('Gerando boletim PDF para:', alunoNome, dados);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'Em desenvolvimento',
                text: 'Funcionalidade de PDF serÃ¡ implementada com biblioteca especÃ­fica',
                confirmButtonColor: '#8B5CF6'
            });
        } else {
            console.log('Funcionalidade de PDF sera implementada com biblioteca especifica');
        }
    }
}
