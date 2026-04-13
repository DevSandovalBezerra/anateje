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

class ListEventsHandler extends BaseHandler implements RequestHandlerInterface
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

        $memberId = 0;
        if ($actorId > 0) {
            $member = Helpers::memberProfile($db, $actorId);
            if ($member) {
                $memberId = (int) $member['id'];
            }
        }

        $sql = "SELECT
                e.id, e.titulo, e.descricao, e.local, e.inicio_em, e.fim_em, e.vagas, e.status,
                e.access_scope, e.waitlist_enabled, e.checkin_enabled, e.max_waitlist,
                e.imagem_url, e.link,
                (SELECT COUNT(*) FROM event_registrations er1 WHERE er1.event_id = e.id AND er1.status IN ('registered','checked_in')) AS occupied_count,
                (SELECT COUNT(*) FROM event_registrations er2 WHERE er2.event_id = e.id AND er2.status = 'waitlisted') AS waitlisted_count,
                (SELECT COUNT(*) FROM event_registrations er3 WHERE er3.event_id = e.id AND er3.status = 'checked_in') AS checked_in_count";

        if ($memberId > 0) {
            $sql .= ",
                (SELECT er4.status FROM event_registrations er4 WHERE er4.event_id = e.id AND er4.member_id = " . $memberId . " LIMIT 1) AS my_registration_status,
                (SELECT er5.checked_in_at FROM event_registrations er5 WHERE er5.event_id = e.id AND er5.member_id = " . $memberId . " LIMIT 1) AS my_checked_in_at";
        } else {
            $sql .= ", NULL AS my_registration_status, NULL AS my_checked_in_at";
        }

        $sql .= " FROM events e
            WHERE e.status = 'published'
            ORDER BY e.inicio_em ASC, e.id ASC";

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $vagas = $row['vagas'] !== null ? (int) $row['vagas'] : null;
            $occupied = (int) ($row['occupied_count'] ?? 0);
            $row['vagas_restantes'] = $vagas === null ? null : max(0, $vagas - $occupied);
        }

        return $this->json(['events' => $rows]);
    }
}
