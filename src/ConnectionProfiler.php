<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

use AsceticSoft\Rowcast\ConnectionInterface;

/**
 * Subscribes to Rowcast connection query events to record SQL metrics.
 */
final class ConnectionProfiler
{
    /** @var array<int, QueryProfileHandle> */
    private array $handles = [];

    public function __construct(
        ConnectionInterface $connection,
        private readonly ProfilerInterface $profiler,
    ) {
        $connection->onBeforeQuery($this->beforeQuery(...));
        $connection->onAfterQuery($this->afterQuery(...));
    }

    /**
     * @param array<string|int, mixed> $params
     */
    private function beforeQuery(string $sql, array $params): void
    {
        $this->handles[] = $this->profiler->start('query', $sql, $params);
    }

    /**
     * @param array<string|int, mixed> $params
     */
    private function afterQuery(string $sql, array $params, float $duration, ?\Throwable $error): void
    {
        $handle = array_pop($this->handles) ?? $this->profiler->start('query', $sql, $params);
        $this->profiler->finish($handle, $error, [
            'durationMs' => $duration * 1000.0,
        ]);
    }
}
