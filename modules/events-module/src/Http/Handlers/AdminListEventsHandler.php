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
use PDO;

class AdminListEventsHandler extends BaseHandler implements RequestHandlerInterface
{
    public function __construct(
        Psr17Factory $factory,
        private DbConnection $dbConnection,
        private AuthContextProvider $auth,
        private PermissionChecker $permission
    ) {
        parent::__construct($factory);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = $this->auth->getContext();
        $db = $this->dbConnection->getPDO();

        if (!$this->permission->hasPermission($context, 'admin.eventos.view')) {
            return $this->error('FORBIDDEN', 'Sem permissao', 403);
        }

        $rows = $db->query("SELECT
                e.*,
                (SELECT COUNT(*) FROM event_registrations r1 WHERE r1.event_id = e.id AND r1.status IN ('registered','checked_in')) AS occupied_count,
                (SELECT COUNT(*) FROM event_registrations r2 WHERE r2.event_id = e.id AND r2.status = 'waitlisted') AS waitlisted_count,
                (SELECT COUNT(*) FROM event_registrations r3 WHERE r3.event_id = e.id AND r3.status = 'checked_in') AS checked_in_count
            FROM events e
            ORDER BY e.inicio_em DESC, e.id DESC")->fetchAll(PDO::FETCH_ASSOC);

        $statusCounts = [
            'draft' => 0,
            'published' => 0,
            'archived' => 0,
            'other' => 0,
        ];
        foreach ($rows as &$row) {
            $vagas = $row['vagas'] !== null ? (int) $row['vagas'] : null;
            $occupied = (int) ($row['occupied_count'] ?? 0);
            $row['vagas_restantes'] = $vagas === null ? null : max(0, $vagas - $occupied);

            $status = strtolower((string) ($row['status'] ?? ''));
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            } else {
                $statusCounts['other']++;
            }
        }
        unset($row);

        return $this->json([
            'events' => $rows,
            'meta' => [
                'total' => count($rows),
                'status_counts' => $statusCounts,
            ],
        ]);
    }
}