<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

/**
 * Opaque handle for an in-flight profiled operation; created by {@see RowcastProfiler::start()}.
 */
final class QueryProfileHandle
{
    /**
     * @param array<string|int, mixed> $params
     * @param array<string, mixed>     $context
     */
    public function __construct(
        public readonly string $id,
        public readonly string $operation,
        public readonly string $sql,
        public readonly array $params,
        public readonly array $context,
        /** Nanoseconds from {@see hrtime()} */
        public readonly int $startHrNs,
        public readonly int $startMemoryUsage,
    ) {
    }
}
