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

class RegisterEventHandler extends BaseHandler implements RequestHandlerInterface
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
        if (strtoupper((string) ($member['status'] ?? '')) !== 'ATIVO') {
            return $this->error('MEMBER_INACTIVE', 'Somente associados ativos podem se inscrever', 422);
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
            if (!$event || $event['status'] !== 'published') {
                throw new RuntimeException('EVENT_NOT_FOUND');
            }
            if (!Helpers::accessAllowed($event, $member)) {
                throw new RuntimeException('EVENT_NOT_ALLOWED_FOR_CATEGORY');
            }

            $sr = $db->prepare('SELECT id, status FROM event_registrations WHERE event_id = ? AND member_id = ? LIMIT 1 FOR UPDATE');
            $sr->execute([$eventId, (int) $member['id']]);
            $existing = $sr->fetch(PDO::FETCH_ASSOC);

            if ($existing && (string) $existing['status'] === 'checked_in') {
                throw new RuntimeException('ALREADY_CHECKED_IN');
            }

            $occupied = Helpers::countOccupied($db, $eventId);
            $hasSlot = ($event['vagas'] === null) ? true : ($occupied < (int) $event['vagas']);

            $targetStatus = 'registered';
            $waitlistedAt = null;
            $checkedInAt = null;
            $canceledAt = null;

            if (!$hasSlot) {
                $waitlistEnabled = (int) ($event['waitlist_enabled'] ?? 0) === 1;
                if (!$waitlistEnabled) {
                    throw new RuntimeException('SEM_VAGAS');
                }

                $maxWaitlist = $event['max_waitlist'] !== null ? (int) $event['max_waitlist'] : null;
                if ($maxWaitlist !== null && $maxWaitlist > 0) {
                    $waitCount = Helpers::countWaitlisted($db, $eventId);
                    if (!$existing || (string) $existing['status'] !== 'waitlisted') {
                        if ($waitCount >= $maxWaitlist) {
                            throw new RuntimeException('WAITLIST_FULL');
                        }
                    }
                }

                $targetStatus = 'waitlisted';
                $waitlistedAt = date('Y-m-d H:i:s');
            }

            if ($existing) {
                $up = $db->prepare("UPDATE event_registrations
                    SET status = ?, waitlisted_at = ?, checked_in_at = ?, canceled_at = ?
                    WHERE id = ?");
                $up->execute([$targetStatus, $waitlistedAt, $checkedInAt, $canceledAt, (int) $existing['id']]);
            } else {
                $ins = $db->prepare("INSERT INTO event_registrations
                    (event_id, member_id, status, waitlisted_at, checked_in_at, canceled_at)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$eventId, (int) $member['id'], $targetStatus, $waitlistedAt, $checkedInAt, $canceledAt]);
            }

            $db->commit();
            return $this->json([
                'registered' => $targetStatus === 'registered',
                'waitlisted' => $targetStatus === 'waitlisted',
                'status' => $targetStatus,
            ]);
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $code = $e->getMessage();
            if ($code === 'EVENT_NOT_FOUND') {
                return $this->error('NOT_FOUND', 'Evento nao encontrado', 404);
            }
            if ($code === 'EVENT_NOT_ALLOWED_FOR_CATEGORY') {
                return $this->error('FORBIDDEN', 'Evento indisponivel para sua categoria', 403);
            }
            if ($code === 'SEM_VAGAS') {
                return $this->error('SEM_VAGAS', 'Evento sem vagas', 422);
            }
            if ($code === 'WAITLIST_FULL') {
                return $this->error('WAITLIST_FULL', 'Fila de espera lotada', 422);
            }
            if ($code === 'ALREADY_CHECKED_IN') {
                return $this->error('ALREADY_CHECKED_IN', 'Presenca ja confirmada neste evento', 422);
            }
            return $this->error('FAIL', 'Falha ao registrar no evento', 500);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Erro events.register: ' . $e->getMessage());
            return $this->error('FAIL', 'Falha ao registrar no evento', 500);
        }
    }
}