<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

final class RowcastProfiler implements ProfilerInterface
{
    public function __construct(
        private readonly QueryProfileStoreInterface  $store,
        private readonly ParameterSanitizerInterface $sanitizer,
        private readonly SqlClassifier               $classifier = new SqlClassifier(),
        private float                                $slowQueryThresholdMs = 50.0,
        private bool                                 $collectParams = true,
    ) {
    }

    public function setSlowQueryThresholdMs(float $ms): void
    {
        $this->slowQueryThresholdMs = $ms;
    }

    public function setCollectParams(bool $collect): void
    {
        $this->collectParams = $collect;
    }

    public function start(string $operation, string $sql, array $params = [], array $context = []): QueryProfileHandle
    {
        return new QueryProfileHandle(
            id: bin2hex(random_bytes(8)),
            operation: $operation,
            sql: $sql,
            params: $params,
            context: $context,
            startHrNs: hrtime(true),
            startMemoryUsage: memory_get_usage(true),
        );
    }

    public function finish(QueryProfileHandle $handle, ?\Throwable $error = null, mixed $result = null): void
    {
        $durationMs = $this->resolveDurationMs($handle, $result);
        $memNow = memory_get_usage(true);
        $memoryDelta = $memNow - $handle->startMemoryUsage;

        $rowCount = $this->resolveRowCount($result, $error);
        $sqlNorm = $this->classifier->normalize($handle->sql);
        $fingerprint = $this->classifier->fingerprint($handle->sql);
        $type = $this->classifier->classify($handle->sql);

        $paramsSanitized = $this->sanitizer->sanitize($handle->params, $this->collectParams);

        $profile = new QueryProfile(
            id: $handle->id,
            operation: $handle->operation,
            sql: $handle->sql,
            sqlNormalized: $sqlNorm,
            fingerprint: $fingerprint,
            statementType: $type,
            paramsSanitized: $paramsSanitized,
            durationMs: $durationMs,
            memoryDeltaBytes: $memoryDelta,
            rowCount: $rowCount,
            slow: $durationMs >= $this->slowQueryThresholdMs,
            errorClass: $error !== null ? $error::class : null,
            errorMessage: $error?->getMessage(),
            context: $handle->context,
        );

        $this->store->record($profile);
    }

    private function resolveDurationMs(QueryProfileHandle $handle, mixed $result): float
    {
        if (\is_array($result) && (\is_int($result['durationMs'] ?? null) || \is_float($result['durationMs'] ?? null))) {
            return (float) $result['durationMs'];
        }

        return (hrtime(true) - $handle->startHrNs) / 1_000_000.0;
    }

    private function resolveRowCount(mixed $result, ?\Throwable $error): ?int
    {
        if ($error !== null) {
            return null;
        }

        if (\is_int($result)) {
            return $result;
        }

        if (\is_array($result)) {
            if (array_is_list($result)) {
                return \count($result);
            }

            if (isset($result['rowCount']) && \is_int($result['rowCount'])) {
                return $result['rowCount'];
            }

            return null;
        }

        if ($result instanceof \PDOStatement) {
            return $result->rowCount();
        }

        return null;
    }
}
