<?php
// API de Pessoas - padrao ANATEJE

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/_bootstrap.php';

class PessoasAPI
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    private function limparDocumento(?string $documento): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $documento);
        return $digits !== '' ? $digits : null;
    }

    private function validarCPF(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += ((int) $cpf[$c]) * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$c] !== $d) {
                return false;
            }
        }
        return true;
    }

    private function validarCNPJ(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $length = strlen($cnpj) - 2;
        $numbers = substr($cnpj, 0, $length);
        $digits = substr($cnpj, $length);
        $sum = 0;
        $pos = $length - 7;
        for ($i = $length; $i >= 1; $i--) {
            $sum += ((int) $numbers[$length - $i]) * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        $result = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);
        if ((int) $digits[0] !== $result) {
            return false;
        }

        $length++;
        $numbers = substr($cnpj, 0, $length);
        $sum = 0;
        $pos = $length - 7;
        for ($i = $length; $i >= 1; $i--) {
            $sum += ((int) $numbers[$length - $i]) * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        $result = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);
        return (int) $digits[1] === $result;
    }

    private function validarDocumento(?string $documento): bool
    {
        if ($documento === null || $documento === '') {
            return true;
        }
        $documento = $this->limparDocumento($documento);
        if ($documento === null) {
            return true;
        }
        if (strlen($documento) === 11) {
            return $this->validarCPF($documento);
        }
        if (strlen($documento) === 14) {
            return $this->validarCNPJ($documento);
        }
        return false;
    }

    private function existeDocumentoDuplicado(?string $documento, ?int $ignorarId = null): bool
    {
        $doc = $this->limparDocumento($documento);
        if ($doc === null) {
            return false;
        }

        $sql = "SELECT id FROM pessoas WHERE documento = ?";
        $params = [$doc];
        if ($ignorarId !== null && $ignorarId > 0) {
            $sql .= " AND id <> ?";
            $params[] = $ignorarId;
        }
        $sql .= " LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (bool) $st->fetch(PDO::FETCH_ASSOC);
    }

    public function listar(array $filtros = []): array
    {
        try {
            $sql = "SELECT * FROM pessoas WHERE 1=1";
            $params = [];

            if (!empty($filtros['tipo'])) {
                $sql .= " AND tipo = ?";
                $params[] = (string) $filtros['tipo'];
            }
            if (isset($filtros['ativo'])) {
                $sql .= " AND ativo = ?";
                $params[] = (int) $filtros['ativo'];
            }
            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nome LIKE ? OR documento LIKE ? OR email LIKE ?)";
                $term = '%' . (string) $filtros['buscar'] . '%';
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
            }

            $sql .= " ORDER BY nome ASC";
            $st = $this->db->prepare($sql);
            $st->execute($params);
            return ['success' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Throwable $e) {
            logError('Erro ao listar pessoas: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao listar pessoas'];
        }
    }

    public function obter(int $id): array
    {
        try {
            if ($id <= 0) {
                return ['success' => false, 'message' => 'ID obrigatorio'];
            }

            $st = $this->db->prepare("SELECT * FROM pessoas WHERE id = ?");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Pessoa nao encontrada'];
            }
            return ['success' => true, 'data' => $row];
        } catch (Throwable $e) {
            logError('Erro ao obter pessoa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao obter pessoa'];
        }
    }

    public function criar(array $dados): array
    {
        try {
            $nome = trim((string) ($dados['nome'] ?? ''));
            $tipo = strtolower(trim((string) ($dados['tipo'] ?? '')));
            if ($nome === '' || $tipo === '') {
                return ['success' => false, 'message' => 'Nome e tipo sao obrigatorios'];
            }
            if (!in_array($tipo, ['cliente', 'fornecedor', 'colaborador', 'outro'], true)) {
                return ['success' => false, 'message' => 'Tipo invalido'];
            }

            $documento = $this->limparDocumento($dados['documento'] ?? null);
            if (!$this->validarDocumento($documento)) {
                return ['success' => false, 'message' => 'Documento invalido (CPF/CNPJ)'];
            }
            if ($this->existeDocumentoDuplicado($documento, null)) {
                return ['success' => false, 'message' => 'Documento ja cadastrado'];
            }

            $email = trim((string) ($dados['email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email invalido'];
            }

            $telefone = trim((string) ($dados['telefone'] ?? ''));
            $endereco = trim((string) ($dados['endereco'] ?? ''));
            $observacoes = trim((string) ($dados['observacoes'] ?? ''));
            $ativo = isset($dados['ativo']) && (int) $dados['ativo'] === 0 ? 0 : 1;

            $st = $this->db->prepare("INSERT INTO pessoas
                (nome, tipo, documento, email, telefone, endereco, observacoes, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $st->execute([
                sanitizeInput($nome),
                $tipo,
                $documento,
                $email !== '' ? sanitizeInput($email) : null,
                $telefone !== '' ? sanitizeInput($telefone) : null,
                $endereco !== '' ? sanitizeInput($endereco) : null,
                $observacoes !== '' ? sanitizeInput($observacoes) : null,
                $ativo
            ]);

            return [
                'success' => true,
                'message' => 'Pessoa criada com sucesso',
                'data' => ['id' => (int) $this->db->lastInsertId()]
            ];
        } catch (Throwable $e) {
            logError('Erro ao criar pessoa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar pessoa'];
        }
    }

    public function atualizar(int $id, array $dados): array
    {
        try {
            $atual = $this->obter($id);
            if (empty($atual['success'])) {
                return $atual;
            }

            $campos = [];
            $valores = [];

            if (isset($dados['nome'])) {
                $nome = trim((string) $dados['nome']);
                if ($nome === '') {
                    return ['success' => false, 'message' => 'Nome e obrigatorio'];
                }
                $campos[] = 'nome = ?';
                $valores[] = sanitizeInput($nome);
            }
            if (isset($dados['tipo'])) {
                $tipo = strtolower(trim((string) $dados['tipo']));
                if (!in_array($tipo, ['cliente', 'fornecedor', 'colaborador', 'outro'], true)) {
                    return ['success' => false, 'message' => 'Tipo invalido'];
                }
                $campos[] = 'tipo = ?';
                $valores[] = $tipo;
            }
            if (array_key_exists('documento', $dados)) {
                $documento = $this->limparDocumento($dados['documento']);
                if (!$this->validarDocumento($documento)) {
                    return ['success' => false, 'message' => 'Documento invalido (CPF/CNPJ)'];
                }
                if ($this->existeDocumentoDuplicado($documento, $id)) {
                    return ['success' => false, 'message' => 'Documento ja cadastrado'];
                }
                $campos[] = 'documento = ?';
                $valores[] = $documento;
            }
            if (array_key_exists('email', $dados)) {
                $email = trim((string) $dados['email']);
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return ['success' => false, 'message' => 'Email invalido'];
                }
                $campos[] = 'email = ?';
                $valores[] = $email !== '' ? sanitizeInput($email) : null;
            }
            if (array_key_exists('telefone', $dados)) {
                $telefone = trim((string) $dados['telefone']);
                $campos[] = 'telefone = ?';
                $valores[] = $telefone !== '' ? sanitizeInput($telefone) : null;
            }
            if (array_key_exists('endereco', $dados)) {
                $endereco = trim((string) $dados['endereco']);
                $campos[] = 'endereco = ?';
                $valores[] = $endereco !== '' ? sanitizeInput($endereco) : null;
            }
            if (array_key_exists('observacoes', $dados)) {
                $observacoes = trim((string) $dados['observacoes']);
                $campos[] = 'observacoes = ?';
                $valores[] = $observacoes !== '' ? sanitizeInput($observacoes) : null;
            }
            if (isset($dados['ativo'])) {
                $campos[] = 'ativo = ?';
                $valores[] = ((int) $dados['ativo'] === 0) ? 0 : 1;
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $sql = "UPDATE pessoas SET " . implode(', ', $campos) . " WHERE id = ?";
            $st = $this->db->prepare($sql);
            $st->execute($valores);

            return ['success' => true, 'message' => 'Pessoa atualizada com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao atualizar pessoa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar pessoa'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            $atual = $this->obter($id);
            if (empty($atual['success'])) {
                return $atual;
            }

            $st = $this->db->prepare("SELECT COUNT(*) as total FROM lancamentos_financeiros WHERE pessoa_id = ?");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!empty($row) && (int) ($row['total'] ?? 0) > 0) {
                return ['success' => false, 'message' => 'Nao e possivel excluir pessoa com lancamentos vinculados'];
            }

            $st = $this->db->prepare("DELETE FROM pessoas WHERE id = ?");
            $st->execute([$id]);
            return ['success' => true, 'message' => 'Pessoa excluida com sucesso'];
        } catch (Throwable $e) {
            logError('Erro ao excluir pessoa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao excluir pessoa'];
        }
    }
}

$auth = financeiro_require_auth('pessoas');
$api = new PessoasAPI();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'listar';
    switch ($action) {
        case 'listar':
            financeiro_response($api->listar([
                'tipo' => $_GET['tipo'] ?? null,
                'ativo' => isset($_GET['ativo']) ? (int) $_GET['ativo'] : null,
                'buscar' => $_GET['buscar'] ?? null,
            ]));
            break;
        case 'obter':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            financeiro_response($api->obter($id));
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao invalida'], 404);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = financeiro_input();
    $action = (string) ($input['action'] ?? '');

    switch ($action) {
        case 'criar':
            $result = $api->criar($input);
            if (!empty($result['success'])) {
                $entityId = financeiro_extract_entity_id($result);
                $after = $entityId ? ($api->obter($entityId)['data'] ?? null) : null;
                financeiro_audit($auth, 'create', 'pessoas', $entityId, null, $after, ['action' => 'criar']);
            }
            financeiro_response($result);
            break;
        case 'atualizar':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            $before = $api->obter($id)['data'] ?? null;
            $result = $api->atualizar($id, $input);
            if (!empty($result['success'])) {
                $after = $api->obter($id)['data'] ?? null;
                financeiro_audit($auth, 'update', 'pessoas', $id, $before, $after, ['action' => 'atualizar']);
            }
            financeiro_response($result);
            break;
        case 'excluir':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                financeiro_response(['success' => false, 'message' => 'ID obrigatorio'], 400);
            }
            $before = $api->obter($id)['data'] ?? null;
            $result = $api->excluir($id);
            if (!empty($result['success'])) {
                financeiro_audit($auth, 'delete', 'pessoas', $id, $before, null, ['action' => 'excluir']);
            }
            financeiro_response($result);
            break;
        default:
            financeiro_response(['success' => false, 'message' => 'Acao invalida'], 404);
    }
}

financeiro_response(['success' => false, 'message' => 'Metodo nao permitido'], 405);
