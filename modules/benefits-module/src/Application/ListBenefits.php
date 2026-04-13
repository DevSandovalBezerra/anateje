<?php

declare(strict_types=1);

namespace Anateje\BenefitsModule\Application;

use Anateje\Contracts\DbConnection;
use PDO;

final class ListBenefits
{
    public function __construct(private DbConnection $db)
    {
    }

    public function execute(): array
    {
        $pdo = $this->db->getPdo();
        $rows = $pdo->query('SELECT * FROM benefits ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

        $statusCounts = [
            'active' => 0,
            'inactive' => 0,
            'other' => 0,
        ];

        foreach ($rows as $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            } else {
                $statusCounts['other']++;
            }
        }

        return [
            'benefits' => $rows,
            'meta' => [
                'total' => count($rows),
                'status_counts' => $statusCounts,
            ],
        ];
    }
}

