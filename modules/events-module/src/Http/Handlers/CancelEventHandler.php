<?php

declare(strict_types=1);

namespace Anateje\EventsModule\Http\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Anateje\Contracts\DbConnection;
use Anateje\Contracts\AuthContextProvider;
use Anateje\EventsModule\Application\Helpers;
use PDO;
use RuntimeException;
use Throwable;

class CancelEventHandler extends BaseHandler implements RequestHandlerInterface
{
    public function __construct(
        Psr17Factory $factory,
        private DbConnection $dbConnection,
        private AuthContextProvider $auth
    ) {
        parent::__construct($factory);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->auth->getContext();
        $actorId = $context ? (int) $context['sub'] : 0;
        $db = $this->dbConnection->getPDO();

        $member = Helpers::memberProfile($db, $actorId);
        if (!$member) {
            return $this->error('NO_MEMBER', 'Complete seu perfil antes', 422);
        }

        $in = json_decode((string) $request->getBody(), true) ?? [];
        $eventId = (int) ($in['event_id'] ?? 0);
        if ($eventId <= 0) {
            return $this->error('VALIDATION', 'Evento invalido', 422);
        }

        $db->beginTransaction();
        try {
            $se = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1 FOR UPDATE');
            $se->execute([$eventId]);
            $event = $se->fetch(PDO::FETCH_ASSOC);
            if (!$event) {
                throw new RuntimeException('EVENT_NOT_FOUND');
            }

            $sr = $db->prepare('SELECT id, status FROM event_registrations WHERE event_id = ? AND member_id = ? LIMIT 1 FOR UPDATE');
            $sr->execute([$eventId, (int) $member['id']]);
            $reg = $sr->fetch(PDO::FETCH_ASSOC);
            if (!$reg) {
                $db->commit();
                return $this->json(['canceled' => true, 'promoted' => false]);
            }

            $prevStatus = (string) ($reg['status'] ?? '');
            if ($prevStatus !== 'canceled') {
                $up = $db->prepare("UPDATE event_registrations SET status = 'canceled', canceled_at = ? WHERE id = ?");
                $up->execute([date('Y-m-d H:i:s'), (int) $reg['id']]);
            }

            $promoted = null;
            if (in_array($prevStatus, ['registered', 'checked_in'], true)) {
                $promoted = Helpers::promoteWaitlisted($db, $eventId);
            }

            $db->commit();
            return $this->json([
                'canceled' => true,
                'promoted' => $promoted !== null,
                'promoted_registration_id' => $promoted['registration_id'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($e->getMessage() === 'EVENT_NOT_FOUND') {
                return $this->error('NOT_FOUND', 'Evento nao encontrado', 404);
            }
            return $this->error('FAIL', 'Falha ao cancelar inscricao', 500);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Erro events.cancel: ' . $e->getMessage());
            return $this->error('FAIL', 'Falha ao cancelar inscricao', 500);
        }
    }
}