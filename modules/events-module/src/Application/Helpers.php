<?php

declare(strict_types=1);

namespace Anateje\EventsModule\Application;

use PDO;

class Helpers
{
    public static function streamCsv(string $filename, array $header, array $rows): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header, ';');
        foreach ($rows as $line) {
            fputcsv($out, $line, ';');
        }
        fclose($out);
        exit;
    }

    public static function parsePagination(array $queryParams, int $defaultPerPage = 20, int $maxPerPage = 100): array
    {
        $page = (int) ($queryParams['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $perPage = (int) ($queryParams['per_page'] ?? $defaultPerPage);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    public static function parseRegistrationFilters(array $queryParams): array
    {
        $status = strtolower(trim((string) ($queryParams['status'] ?? '')));
        $allowed = ['registered', 'waitlisted', 'checked_in', 'canceled'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $categoria = strtoupper(trim((string) ($queryParams['categoria'] ?? '')));
        if (!in_array($categoria, ['PARCIAL', 'INTEGRAL'], true)) {
            $categoria = '';
        }

        $q = trim((string) ($queryParams['q'] ?? ''));
        if (strlen($q) > 120) {
            $q = substr($q, 0, 120);
        }

        return [
            'status' => $status,
            'categoria' => $categoria,
            'q' => $q,
        ];
    }

    public static function normalizeBulkIds($rawIds): array
    {
        if (!is_array($rawIds)) {
            return [];
        }

        $ids = [];
        foreach ($rawIds as $raw) {
            $id = (int) $raw;
            if ($id > 0) {
                $ids[$id] = true;
            }
            if (count($ids) >= 500) {
                break;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    public static function registrationWhereSql(int $eventId, array $filters, array &$params): string
    {
        $params = [$eventId];
        $where = ' WHERE er.event_id = ?';

        if (($filters['status'] ?? '') !== '') {
            $where .= ' AND er.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['categoria'] ?? '') !== '') {
            $where .= ' AND m.categoria = ?';
            $params[] = $filters['categoria'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where .= ' AND (m.nome LIKE ? OR m.email_funcional LIKE ? OR m.telefone LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return $where;
    }

    public static function memberProfile(PDO $db, int $userId): ?array
    {
        $st = $db->prepare('SELECT id, categoria, status FROM members WHERE user_id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'categoria' => (string) ($row['categoria'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
        ];
    }

    public static function accessAllowed(array $event, array $member): bool
    {
        $scope = strtoupper((string) ($event['access_scope'] ?? 'ALL'));
        if ($scope === 'ALL') {
            return true;
        }
        if ($scope === 'PARCIAL') {
            return strtoupper((string) ($member['categoria'] ?? '')) === 'PARCIAL';
        }
        if ($scope === 'INTEGRAL') {
            return strtoupper((string) ($member['categoria'] ?? '')) === 'INTEGRAL';
        }
        return true;
    }

    public static function countOccupied(PDO $db, int $eventId): int
    {
        $st = $db->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status IN ('registered','checked_in')");
        $st->execute([$eventId]);
        return (int) $st->fetchColumn();
    }

    public static function countWaitlisted(PDO $db, int $eventId): int
    {
        $st = $db->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status = 'waitlisted'");
        $st->execute([$eventId]);
        return (int) $st->fetchColumn();
    }

    public static function promoteWaitlisted(PDO $db, int $eventId): ?array
    {
        $st = $db->prepare("SELECT id, member_id
            FROM event_registrations
            WHERE event_id = ? AND status = 'waitlisted'
            ORDER BY COALESCE(waitlisted_at, created_at) ASC, id ASC
            LIMIT 1
            FOR UPDATE");
        $st->execute([$eventId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $up = $db->prepare("UPDATE event_registrations
            SET status = 'registered', canceled_at = NULL
            WHERE id = ?");
        $up->execute([(int) $row['id']]);

        return [
            'registration_id' => (int) $row['id'],
            'member_id' => (int) $row['member_id'],
        ];
    }
}
