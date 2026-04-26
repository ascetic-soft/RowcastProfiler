<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

use AsceticSoft\Rowcast\ConnectionInterface;
use AsceticSoft\Rowcast\QueryBuilder\QueryBuilder;
use AsceticSoft\Rowcast\TypeConverter\TypeConverterInterface;

/**
 * Decorates {@see ConnectionInterface} to record SQL metrics via {@see ProfilerInterface}.
 */
final readonly class ConnectionProfiler implements ConnectionInterface
{
    public function __construct(
        private ConnectionInterface $inner,
        private ProfilerInterface $profiler,
    ) {
    }

    public function getTypeConverter(): TypeConverterInterface
    {
        return $this->inner->getTypeConverter();
    }

    public function setTypeConverter(TypeConverterInterface $typeConverter): void
    {
        $this->inner->setTypeConverter($typeConverter);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this, $this->inner->getTypeConverter());
    }

    public function executeQuery(string $sql, array $params = []): \PDOStatement
    {
        $h = $this->profiler->start('executeQuery', $sql, $params);

        try {
            $stmt = $this->inner->executeQuery($sql, $params);
            $this->profiler->finish($h, null, $stmt);

            return $stmt;
        } catch (\Throwable $e) {
            $this->profiler->finish($h, $e);

            throw $e;
        }
    }

    public function executeStatement(string $sql, array $params = []): int
    {
        $h = $this->profiler->start('executeStatement', $sql, $params);

        try {
            $affected = $this->inner->executeStatement($sql, $params);
            $this->profiler->finish($h, null, $affected);

            return $affected;
        } catch (\Throwable $e) {
            $this->profiler->finish($h, $e);

            throw $e;
        }
    }

    public function fetchAllAssociative(string $sql, array $params = []): array
    {
        $h = $this->profiler->start('fetchAllAssociative', $sql, $params);

        try {
            $rows = $this->inner->fetchAllAssociative($sql, $params);
            $this->profiler->finish($h, null, $rows);

            return $rows;
        } catch (\Throwable $e) {
            $this->profiler->finish($h, $e);

            throw $e;
        }
    }

    public function fetchAssociative(string $sql, array $params = []): array|false
    {
        $h = $this->profiler->start('fetchAssociative', $sql, $params);

        try {
            $row = $this->inner->fetchAssociative($sql, $params);
            $this->profiler->finish($h, null, $row === false ? ['rowCount' => 0] : ['rowCount' => 1]);

            return $row;
        } catch (\Throwable $e) {
            $this->profiler->finish($h, $e);

            throw $e;
        }
    }

    public function fetchOne(string $sql, array $params = []): mixed
    {
        $h = $this->profiler->start('fetchOne', $sql, $params);

        try {
            $one = $this->inner->fetchOne($sql, $params);
            $this->profiler->finish($h, null, false === $one ? ['rowCount' => 0] : ['rowCount' => 1]);

            return $one;
        } catch (\Throwable $e) {
            $this->profiler->finish($h, $e);

            throw $e;
        }
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->inner->lastInsertId($name);
    }

    public function beginTransaction(): void
    {
        $this->inner->beginTransaction();
    }

    public function commit(): void
    {
        $this->inner->commit();
    }

    public function rollBack(): void
    {
        $this->inner->rollBack();
    }

    public function transactional(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            /** @var mixed $result */
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();

            throw $e;
        }
    }

    public function toIterable(string $sql, array $params = []): iterable
    {
        $inner = $this->inner;
        $profiler = $this->profiler;
        $h = $profiler->start('toIterable', $sql, $params);

        return (static function () use ($h, $inner, $sql, $params, $profiler): \Generator {
            $count = 0;
            $error = null;

            try {
                foreach ($inner->toIterable($sql, $params) as $row) {
                    ++$count;
                    yield $row;
                }
            } catch (\Throwable $e) {
                $error = $e;

                throw $e;
            } finally {
                $profiler->finish($h, $error, ['rowCount' => $count]);
            }
        })();
    }

    public function getTransactionNestingLevel(): int
    {
        return $this->inner->getTransactionNestingLevel();
    }

    public function getDriverName(): string
    {
        return $this->inner->getDriverName();
    }

    public function getPdo(): \PDO
    {
        return $this->inner->getPdo();
    }

    public function getInner(): ConnectionInterface
    {
        return $this->inner;
    }
}
