<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

/**
 * Immutable snapshot of one profiled SQL call.
 */
final readonly class QueryProfile
{
    /**
     * @param array<string|int, mixed> $paramsSanitized
     * @param array<string, mixed>     $context
     */
    public function __construct(
        public string $id,
        public string $operation,
        public string $sql,
        public string $sqlNormalized,
        public string $fingerprint,
        public SqlStatementType $statementType,
        public array $paramsSanitized,
        public float $durationMs,
        public int $memoryDeltaBytes,
        public ?int $rowCount,
        public bool $slow,
        public ?string $errorClass,
        public ?string $errorMessage,
        public array $context = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'operation' => $this->operation,
            'sql' => $this->sql,
            'sql_normalized' => $this->sqlNormalized,
            'fingerprint' => $this->fingerprint,
            'statement_type' => $this->statementType->value,
            'params' => $this->paramsSanitized,
            'duration_ms' => $this->durationMs,
            'memory_delta_bytes' => $this->memoryDeltaBytes,
            'row_count' => $this->rowCount,
            'slow' => $this->slow,
            'error_class' => $this->errorClass,
            'error_message' => $this->errorMessage,
            'context' => $this->context,
        ];
    }
}
