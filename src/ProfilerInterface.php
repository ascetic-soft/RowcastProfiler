<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

interface ProfilerInterface
{
    /**
     * @param array<string|int, mixed> $params
     * @param array<string, mixed>     $context
     */
    public function start(string $operation, string $sql, array $params = [], array $context = []): QueryProfileHandle;

    /**
     * @param mixed|null $result Row count int, PDOStatement, array, or meta array e.g. ['rowCount' => int]
     */
    public function finish(QueryProfileHandle $handle, ?\Throwable $error = null, mixed $result = null): void;
}
