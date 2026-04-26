<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

/**
 * Opaque handle for an in-flight profiled operation; created by {@see RowcastProfiler::start()}.
 */
final readonly class QueryProfileHandle
{
    /**
     * @param array<string|int, mixed> $params
     * @param array<string, mixed>     $context
     */
    public function __construct(
        public string $id,
        public string $operation,
        public string $sql,
        public array  $params,
        public array  $context,
        /** Nanoseconds from {@see hrtime()} */
        public int    $startHrNs,
        public int    $startMemoryUsage,
    ) {
    }
}
