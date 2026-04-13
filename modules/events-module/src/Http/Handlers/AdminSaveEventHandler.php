<?php

declare(strict_types=1);

namespace Anateje\EventsModule\Http\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Anateje\Contracts\DbConnection;
use Anateje\Contracts\AuthContextProvider;
use Anateje\Contracts\PermissionChecker;
use Anateje\Contracts\AuditLogger;
use PDO;
use Throwable;

class AdminSaveEventHandler extends BaseHandler implements RequestHandlerInterface
{
    public function __construct(
        Psr17Factory $factory,
        private DbConnection $dbConnection,
        private AuthContextProvider $auth,
        private PermissionChecker $permission,
        private AuditLogger $audit
    ) {
        parent::__construct($factory);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->auth->getContext();
        $actorId = $context ? (int) $context['sub'] : 0;
        $db = $this->dbConnection->getPDO();

        $in = json_decode((string) $request->getBody(), true) ?? [];
        $id = (int) ($in['id'] ?? 0);
        $perm = $id > 0 ? 'admin.eventos.edit' : 'admin.eventos.create';

        if (!$this->permission->hasPermission($context, $perm)) {
            return $this->error('FORBIDDEN', 'Sem permissao', 403);
        }

        $titulo = trim((string) ($in['titulo'] ?? ''));
        if ($titulo === '') {
            return $this->error('VALIDATION', 'Titulo e obrigatorio', 422);
        }

        $inicio = $in['inicio_em'] ?? '';
        if (empty($inicio)) {
            return $this->error('VALIDATION', 'Data/hora de inicio invalida', 422);
        }

        $fim = $in['fim_em'] ?? null;
        $status = (string) ($in['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }

        $vagasRaw = $in['vagas'] ?? null;
        $vagas = ($vagasRaw === '' || $vagasRaw === null) ? null : max(0, (int) $vagasRaw);

        $accessScope = strtoupper(trim((string) ($in['access_scope'] ?? 'ALL')));
        if (!in_array($accessScope, ['ALL', 'PARCIAL', 'INTEGRAL'], true)) {
            $accessScope = 'ALL';
        }

        $waitlistEnabled = !empty($in['waitlist_enabled']) ? 1 : 0;
        $checkinEnabled = !empty($in['checkin_enabled']) ? 1 : 0;
        $maxWaitlistRaw = $in['max_waitlist'] ?? null;
        $maxWaitlist = ($maxWaitlistRaw === '' || $maxWaitlistRaw === null) ? null : max(0, (int) $maxWaitlistRaw);
        if ($waitlistEnabled === 0) {
            $maxWaitlist = null;
        }

        $db->beginTransaction();
        try {
            $before = null;
            if ($id > 0) {
                $sb = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
                $sb->execute([$id]);
                $before = $sb->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            $payload = [
                $titulo,
                trim((string) ($in['descricao'] ?? '')) ?: null,
                trim((string) ($in['local'] ?? '')) ?: null,
                $inicio,
                $fim,
                $vagas,
                $status,
                $accessScope,
                $waitlistEnabled,
                $checkinEnabled,
                $maxWaitlist,
                trim((string) ($in['imagem_url'] ?? '')) ?: null,
                trim((string) ($in['link'] ?? '')) ?: null,
            ];

            if ($id > 0) {
                $st = $db->prepare('UPDATE events
                    SET titulo=?, descricao=?, local=?, inicio_em=?, fim_em=?, vagas=?, status=?, access_scope=?, waitlist_enabled=?, checkin_enabled=?, max_waitlist=?, imagem_url=?, link=?
                    WHERE id=?');
                $payload[] = $id;
                $st->execute($payload);
            } else {
                $st = $db->prepare('INSERT INTO events
                    (titulo, descricao, local, inicio_em, fim_em, vagas, status, access_scope, waitlist_enabled, checkin_enabled, max_waitlist, imagem_url, link)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $st->execute($payload);
                $id = (int) $db->lastInsertId();
            }

            $sa = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
            $sa->execute([$id]);
            $after = $sa->fetch(PDO::FETCH_ASSOC) ?: null;

            $this->audit->log($actorId, 'admin.eventos', $before ? 'update' : 'create', 'event', $id, $before, $after, []);

            $db->commit();
            return $this->json(['id' => $id]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Erro events.admin_save: ' . $e->getMessage());
            return $this->error('FAIL', 'Falha ao salvar evento', 500);
        }
    }
}