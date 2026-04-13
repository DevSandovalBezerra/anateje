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

class DetailEventHandler extends BaseHandler implements RequestHandlerInterface
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

        $id = (int) $request->getAttribute('id', 0);
        if ($id <= 0) {
            return $this->error('VALIDATION', 'ID invalido', 422);
        }

        $st = $db->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $event = $st->fetch(PDO::FETCH_ASSOC);
        if (!$event || ($event['status'] !== 'published' && empty($context['is_admin']))) {
            return $this->error('NOT_FOUND', 'Evento nao encontrado', 404);
        }

        $member = Helpers::memberProfile($db, $actorId);
        $registration = null;
        if ($member) {
            $sr = $db->prepare('SELECT id, status, waitlisted_at, checked_in_at, canceled_at, created_at
                FROM event_registrations
                WHERE event_id = ? AND member_id = ? LIMIT 1');
            $sr->execute([$id, (int) $member['id']]);
            $registration = $sr->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $occupied = Helpers::countOccupied($db, $id);
        $waitlisted = Helpers::countWaitlisted($db, $id);
        $vagas = $event['vagas'] !== null ? (int) $event['vagas'] : null;
        $event['occupied_count'] = $occupied;
        $event['waitlisted_count'] = $waitlisted;
        $event['vagas_restantes'] = $vagas === null ? null : max(0, $vagas - $occupied);

        return $this->json(['event' => $event, 'registration' => $registration]);
    }
}
