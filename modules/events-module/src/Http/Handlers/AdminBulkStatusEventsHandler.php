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
use Anateje\EventsModule\Application\Helpers;
use PDO;
use Throwable;

class AdminBulkStatusEventsHandler extends BaseHandler implements RequestHandlerInterface
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

        if (!$this->permission->hasPermission($context, 'admin.eventos.edit')) {
            return $this->error('FORBIDDEN', 'Sem permissao', 403);
        }

        $in = json_decode((string) $request->getBody(), true) ?? [];
        $ids = Helpers::normalizeBulkIds($in['ids'] ?? []);
        $targetStatus = strtolower(trim((string) ($in['status'] ?? '')));

        if (!in_array($targetStatus, ['draft', 'published', 'archived'], true)) {
            return $this->error('VALIDATION', 'Status alvo invalido para lote', 422);
        }
        if (!$ids) {
            return $this->error('VALIDATION', 'Selecione ao menos um evento', 422);
        }

        $reason = trim((string) ($in['reason'] ?? ''));
        if (strlen($reason) > 180) {
            $reason = substr($reason, 0, 180);
        }

        $summary = [
            'requested' => count($ids),
            'updated' => 0,
            'unchanged' => 0,
            'not_found' => 0,
        ];

        $db->beginTransaction();
        try {
            $sel = $db->prepare('SELECT id, titulo, status, inicio_em, fim_em FROM events WHERE id = ? LIMIT 1 FOR UPDATE');
            $upd = $db->prepare('UPDATE events SET status = ? WHERE id = ?');

            foreach ($ids as $id) {
                $sel->execute([$id]);
                $before = $sel->fetch(PDO::FETCH_ASSOC);
                if (!$before) {
                    $summary['not_found']++;
                    continue;
                }

                $oldStatus = strtolower((string) ($before['status'] ?? ''));
                if ($oldStatus === $targetStatus) {
                    $summary['unchanged']++;
                    continue;
                }

                $upd->execute([$targetStatus, $id]);
                $after = $before;
                $after['status'] = $targetStatus;

                $this->audit->log(
                    $actorId,
                    'admin.eventos',
                    'bulk_status',
                    'event',
                    $id,
                    $before,
                    $after,
                    ['reason' => $reason !== '' ? $reason : null]
                );
                $summary['updated']++;
            }

            $db->commit();
            return $this->json([
                'target_status' => $targetStatus,
                'requested' => $summary['requested'],
                'updated' => $summary['updated'],
                'unchanged' => $summary['unchanged'],
                'not_found' => $summary['not_found'],
            ]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Erro events.admin_bulk_status: ' . $e->getMessage());
            return $this->error('FAIL', 'Falha ao atualizar status em lote', 500);
        }
    }
}