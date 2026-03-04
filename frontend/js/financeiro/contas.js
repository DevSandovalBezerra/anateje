// ANATEJE - JS Contas a Pagar/Receber com Parcelas
// Fluxo simples, leve e moderno

let inicializandoContas = false;
let contasInicializado = false;
let btnNovaHandler = null;
let btnFiltrarHandler = null;
let btnCancelHandler = null;
let btnFecharModalHandler = null;
let btnFecharParcelasHandler = null;
let formLancHandler = null;

const baseUrl = (typeof ApiConfig !== 'undefined') ? ApiConfig.getBaseUrl() : '';

// Elements
const tbodyLanc = () => document.getElementById('tbodyLancamentos');
const filtroTipo = () => document.getElementById('filtroTipo');
const filtroStatus = () => document.getElementById('filtroStatus');
const filtroBusca = () => document.getElementById('filtroBusca');

const modalLancamento = () => document.getElementById('modalLancamento');
const modalParcelas = () => document.getElementById('modalParcelas');

const inputTipo = () => document.getElementById('inputTipo');
const inputDataEmissao = () => document.getElementById('inputDataEmissao');
const inputDescricao = () => document.getElementById('inputDescricao');
const inputValorTotal = () => document.getElementById('inputValorTotal');
const inputVencimento = () => document.getElementById('inputVencimento');
const inputParcelas = () => document.getElementById('inputParcelas');
const inputPrimeiroVencimento = () => document.getElementById('inputPrimeiroVencimento');

let lancamentos = [];
let contaAtual = null;

async function listar() {
  try {
    let url = `${baseUrl}/api/financeiro/contas.php?action=listar`;
    const params = new URLSearchParams();
    const t = filtroTipo()?.value || '';
    const s = filtroStatus()?.value || '';
    const q = filtroBusca()?.value || '';
    if (t) params.append('tipo', t);
    if (s) params.append('status', s);

    if (params.toString()) url += `&${params.toString()}`;
    const resp = await fetch(url, { credentials: 'include' });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message || 'Erro ao listar');
    lancamentos = json.data || [];

    const term = q.trim().toLowerCase();
    const filtered = term ? lancamentos.filter(l => (l.descricao || '').toLowerCase().includes(term)) : lancamentos;
    renderLancamentos(filtered);
  } catch (err) {
    console.error('listar error', err);
    const tbody = tbodyLanc();
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">Erro ao carregar lançamentos</td></tr>`;
    }
  }
}

function renderLancamentos(items) {
  const tbody = tbodyLanc();
  if (!tbody) return;

  if (!items || items.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-secondary-dark-gray">Nenhum lançamento encontrado</td></tr>`;
    return;
  }

  const rows = items.map(l => {
    const tipoLabel = l.tipo === 'pagar' ? 'Pagar' : 'Receber';
    const tipoClass = l.tipo === 'pagar' ? 'badge-error' : 'badge-success';
    const valor = parseFloat(l.valor_total || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const venc = l.data_vencimento ? formatDate(l.data_vencimento) : '-';
    const statusLabel = statusBadge(l.status);
    const parcelas = `${l.parcelas_pagas || 0}/${l.total_parcelas || 0}`;
    return `
      <tr>
        <td class="px-4 py-2"><span class="${tipoClass}">${tipoLabel}</span></td>
        <td class="px-4 py-2 text-secondary-black">${escapeHtml(l.descricao || '')}</td>
        <td class="px-4 py-2 font-medium text-secondary-black">${valor}</td>
        <td class="px-4 py-2 text-secondary-dark-gray">${venc}</td>
        <td class="px-4 py-2">${statusLabel}</td>
        <td class="px-4 py-2 text-secondary-dark-gray">${parcelas}</td>
        <td class="px-4 py-2">
          <div class="flex space-x-2">
            <button class="text-purple-600 hover:text-purple-700" data-action="ver" data-id="${l.id}" title="Ver Parcelas"><i data-lucide="list" class="w-4 h-4"></i></button>
            <button class="text-red-500 hover:text-red-600" data-action="cancelar" data-id="${l.id}" title="Cancelar"><i data-lucide="x-circle" class="w-4 h-4"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
  tbody.innerHTML = rows;
  if (typeof lucide !== 'undefined') lucide.createIcons();

  tbody.querySelectorAll('button[data-action="ver"]').forEach(btn => {
    btn.addEventListener('click', () => abrirParcelas(parseInt(btn.dataset.id,10)));
  });
  tbody.querySelectorAll('button[data-action="cancelar"]').forEach(btn => {
    btn.addEventListener('click', () => cancelarConta(parseInt(btn.dataset.id,10)));
  });
}

function statusBadge(status) {
  const map = {
    'pendente': 'badge-warning',
    'parcial': 'badge-info',
    'pago': 'badge-success',
    'cancelado': 'badge-error'
  };
  const cls = map[status] || 'badge-secondary';
  const label = (status || '').charAt(0).toUpperCase() + (status || '').slice(1);
  return `<span class="${cls}">${label}</span>`;
}

function formatDate(iso) {
  try {
    const d = new Date(iso);
    return d.toLocaleDateString('pt-BR');
  } catch { return iso; }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text ?? '';
  return div.innerHTML;
}

function abrirModalLanc() {
  const modal = modalLancamento();
  if (modal) modal.classList.remove('hidden');
}

function fecharModalLanc() {
  const modal = modalLancamento();
  if (modal) modal.classList.add('hidden');
}

function abrirParcelas(id) {
  carregarParcelas(id);
}

async function carregarParcelas(id) {
  try {
    const url = `${baseUrl}/api/financeiro/contas.php?action=obter&id=${id}`;
    const resp = await fetch(url, { credentials: 'include' });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message || 'Erro ao carregar');
    contaAtual = json.data?.conta || null;
    const pars = json.data?.parcelas || [];

    const info = document.getElementById('parcelasInfo');
    if (info && contaAtual) {
      info.innerHTML = `
        <div class="text-sm">
          <p><strong>Descrição:</strong> ${escapeHtml(contaAtual.descricao || '')}</p>
          <p><strong>Valor total:</strong> ${parseFloat(contaAtual.valor_total || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</p>
        </div>
      `;
    }

    const tbody = document.getElementById('tbodyParcelas');
    if (!tbody) return;

    if (pars.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-secondary-dark-gray">Nenhuma parcela</td></tr>`;
    } else {
      tbody.innerHTML = pars.map(p => {
        const val = parseFloat(p.valor_parcela || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const venc = p.data_vencimento ? formatDate(p.data_vencimento) : '-';
        const status = statusBadge(p.status);
        const pago = p.data_pagamento ? `${formatDate(p.data_pagamento)} (${parseFloat(p.valor_pago||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'})})` : '-';
        const disabled = p.status === 'paga' ? 'disabled' : '';
        return `
          <tr>
            <td class="px-4 py-2 text-secondary-black">${p.numero_parcela}</td>
            <td class="px-4 py-2 font-medium text-secondary-black">${val}</td>
            <td class="px-4 py-2 text-secondary-dark-gray">${venc}</td>
            <td class="px-4 py-2">${status}</td>
            <td class="px-4 py-2 text-secondary-dark-gray">${pago}</td>
            <td class="px-4 py-2">
              <button class="btn-primary-sm" data-action="pagar" data-id="${p.id}" ${disabled}>Registrar Pagamento</button>
            </td>
          </tr>
        `;
      }).join('');

      tbody.querySelectorAll('button[data-action="pagar"]').forEach(btn => {
        btn.addEventListener('click', () => abrirPagamento(parseInt(btn.dataset.id,10)));
      });
    }

    const modal = modalParcelas();
    if (modal) modal.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
  } catch (err) {
    console.error('parcelas error', err);
    alert('Erro ao carregar parcelas: ' + err.message);
  }
}

function fecharParcelas() {
  const modal = modalParcelas();
  if (modal) modal.classList.add('hidden');
}

function abrirPagamento(parcelaId) {
  const valor = prompt('Valor pago (R$):');
  if (!valor) return;
  const data = prompt('Data do pagamento (AAAA-MM-DD):', new Date().toISOString().slice(0,10));
  if (!data) return;
  registrarPagamento(parcelaId, valor, data);
}

async function registrarPagamento(parcelaId, valorPago, dataPagamento) {
  try {
    const form = new FormData();
    form.append('action', 'registrar_pagamento');
    form.append('parcela_id', parcelaId);
    form.append('valor_pago', valorPago);
    form.append('data_pagamento', dataPagamento);

    const resp = await fetch(`${baseUrl}/api/financeiro/contas.php`, {
      method: 'POST',
      body: form,
      credentials: 'include'
    });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message || 'Erro ao pagar');
    await carregarParcelas(contaAtual?.conta?.id || contaAtual?.id);
    await listar();
    alert('Pagamento registrado.');
  } catch (err) {
    console.error('pagar error', err);
    alert('Erro ao registrar pagamento: ' + err.message);
  }
}

async function cancelarConta(id) {
  if (!confirm('Cancelar este lançamento?')) return;
  try {
    const form = new FormData();
    form.append('action', 'excluir');
    form.append('id', id);
    const resp = await fetch(`${baseUrl}/api/financeiro/contas.php`, {
      method: 'POST',
      body: form,
      credentials: 'include'
    });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message || 'Erro ao cancelar');
    await listar();
    alert('Lançamento cancelado.');
  } catch (err) {
    console.error('cancelar error', err);
    alert('Erro ao cancelar: ' + err.message);
  }
}

async function salvarLancamento(e) {
  e.preventDefault();
  try {
    const form = new FormData();
    form.append('action', 'criar');
    form.append('tipo', inputTipo().value);
    form.append('descricao', inputDescricao().value.trim());
    form.append('valor_total', inputValorTotal().value);
    form.append('data_emissao', inputDataEmissao().value);
    if (inputVencimento().value) form.append('data_vencimento', inputVencimento().value);
    form.append('qtd_parcelas', inputParcelas().value);
    form.append('primeiro_vencimento', inputPrimeiroVencimento().value);

    const resp = await fetch(`${baseUrl}/api/financeiro/contas.php`, {
      method: 'POST',
      body: form,
      credentials: 'include'
    });
    const json = await resp.json();
    if (!json.success) throw new Error(json.message || 'Erro ao criar');
    fecharModalLanc();
    await listar();
    alert('Lançamento criado com sucesso.');
  } catch (err) {
    console.error('criar error', err);
    alert('Erro ao salvar: ' + err.message);
  }
}

function bindUI() {
  const btnNova = document.getElementById('btnNovaConta');
  const btnFiltrar = document.getElementById('btnFiltrar');
  const btnCancel = document.getElementById('btnCancelar');
  const btnFecharModal = document.getElementById('btnFecharModal');
  const btnFecharParcelas = document.getElementById('btnFecharParcelas');
  const formLanc = document.getElementById('formLancamento');

  if (btnNovaHandler && btnNova) {
    btnNova.removeEventListener('click', btnNovaHandler);
  }
  if (btnFiltrarHandler && btnFiltrar) {
    btnFiltrar.removeEventListener('click', btnFiltrarHandler);
  }
  if (btnCancelHandler && btnCancel) {
    btnCancel.removeEventListener('click', btnCancelHandler);
  }
  if (btnFecharModalHandler && btnFecharModal) {
    btnFecharModal.removeEventListener('click', btnFecharModalHandler);
  }
  if (btnFecharParcelasHandler && btnFecharParcelas) {
    btnFecharParcelas.removeEventListener('click', btnFecharParcelasHandler);
  }
  if (formLancHandler && formLanc) {
    formLanc.removeEventListener('submit', formLancHandler);
  }

  btnNovaHandler = () => abrirModalLanc();
  btnFiltrarHandler = () => listar();
  btnCancelHandler = () => fecharModalLanc();
  btnFecharModalHandler = () => fecharModalLanc();
  btnFecharParcelasHandler = () => fecharParcelas();
  formLancHandler = (e) => salvarLancamento(e);

  if (btnNova) btnNova.addEventListener('click', btnNovaHandler);
  if (btnFiltrar) btnFiltrar.addEventListener('click', btnFiltrarHandler);
  if (btnCancel) btnCancel.addEventListener('click', btnCancelHandler);
  if (btnFecharModal) btnFecharModal.addEventListener('click', btnFecharModalHandler);
  if (btnFecharParcelas) btnFecharParcelas.addEventListener('click', btnFecharParcelasHandler);
  if (formLanc) formLanc.addEventListener('submit', formLancHandler);
}

async function initContas() {
  if (inicializandoContas || contasInicializado) {
    return;
  }
  inicializandoContas = true;

  setTimeout(async () => {
    const container = document.getElementById('tbodyLancamentos');
    if (!container) {
      inicializandoContas = false;
      setTimeout(async () => {
        if (document.getElementById('tbodyLancamentos')) {
          await initContas();
        }
      }, 200);
      return;
    }

    bindUI();
    await listar();

    inicializandoContas = false;
    contasInicializado = true;
  }, 100);
}

// Inicialização
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initContas);
} else {
  // Se o documento já estiver carregado, inicializar imediatamente
  initContas();
}

