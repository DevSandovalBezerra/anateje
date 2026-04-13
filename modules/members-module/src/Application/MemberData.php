<?php

declare(strict_types=1);

namespace Anateje\MembersModule\Application;

final class MemberData
{
    public static function onlyDigits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    public static function validCpf(string $cpf): bool
    {
        $cpf = self::onlyDigits($cpf);
        if (strlen($cpf) !== 11) {
            return false;
        }
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += ((int) $cpf[$c]) * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$c] !== $d) {
                return false;
            }
        }

        return true;
    }

    public static function parseDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $dt = date_create($value);
        if (!$dt) {
            return null;
        }

        return $dt->format('Y-m-d');
    }

    public static function parseMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
        if (!is_numeric($text)) {
            return null;
        }

        return (float) $text;
    }

    public static function generateTempPassword(int $length = 10): string
    {
        $length = max(8, min($length, 20));
        $bytes = bin2hex(random_bytes((int) ceil($length / 2)));
        return substr($bytes, 0, $length);
    }
}

