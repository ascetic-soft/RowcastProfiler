<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

/**
 * Normalizes SQL for display, classifies statement kind, and builds a coarse fingerprint for duplicate detection.
 */
final class SqlClassifier
{
    /**
     * Collapses whitespace; useful for display and stable fingerprints.
     */
    public function normalize(string $sql): string
    {
        $sql = trim($sql);

        return preg_replace('/\s+/', ' ', $sql) ?? $sql;
    }

    public function classify(string $sql): SqlStatementType
    {
        $head = $this->firstToken($sql);

        return match ($head) {
            'SELECT', 'WITH' => SqlStatementType::Select,
            'INSERT' => SqlStatementType::Insert,
            'UPDATE' => SqlStatementType::Update,
            'DELETE' => SqlStatementType::Delete,
            'CREATE', 'ALTER', 'DROP', 'TRUNCATE' => SqlStatementType::Ddl,
            default => SqlStatementType::Other,
        };
    }

    /**
     * Fingerprint for grouping similar queries (whitespace-insensitive; literals not normalized in MVP).
     */
    public function fingerprint(string $sql): string
    {
        return strtolower($this->normalize($sql));
    }

    private function firstToken(string $sql): string
    {
        $normalized = ltrim($sql);

        if ($normalized === '') {
            return '';
        }

        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', $normalized, $m)) {
            return '';
        }

        return strtoupper($m[1]);
    }
}
