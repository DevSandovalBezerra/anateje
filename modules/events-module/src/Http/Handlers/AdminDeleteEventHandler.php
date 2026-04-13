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
use RuntimeException;
use Throwable;

class AdminDeleteEventHandler extends BaseHandler implements RequestHandlerInterface
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

        if (!$this->permission->hasPermission($context, 'admin.eventos.delete')) {
            return $this->error('FORBIDDEN', 'Sem permissao', 403);
        }

        $in = json_decode((string) $request->getBody(), true) ?? [];
        $id = (int) ($in['id'] ?? 0);
        if ($id <= 0) {
            return $this->error('VALIDATION', 'ID invalido', 422);
        }

        $db->beginTransaction();
        try {
            $se = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
            $se->execute([$id]);
            $before = $se->fetch(PDO::FETCH_ASSOC);
            if (!$before) {
                throw new RuntimeException('EVENT_NOT_FOUND');
            }

            $db->prepare('DELETE FROM event_registrations WHERE event_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);

            $this->audit->log($actorId, 'admin.eventos', 'delete', 'event', $id, $before, null, []);
            $db->commit();

            return $this->json(['deleted' => true]);
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($e->getMessage() === 'EVENT_NOT_FOUND') {
                return $this->error('NOT_FOUND', 'Evento nao encontrado', 404);
            }
            return $this->error('FAIL', 'Falha ao excluir evento', 500);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Erro events.admin_delete: ' . $e->getMessage());
            return $this->error('FAIL', 'Falha ao excluir evento', 500);
        }
    }
}