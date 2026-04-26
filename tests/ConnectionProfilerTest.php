<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler\Tests;

use AsceticSoft\Rowcast\Connection;
use AsceticSoft\RowcastProfiler\ConnectionProfiler;
use AsceticSoft\RowcastProfiler\DefaultParameterSanitizer;
use AsceticSoft\RowcastProfiler\InMemoryQueryProfileStore;
use AsceticSoft\RowcastProfiler\RowcastProfiler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConnectionProfiler::class)]
final class ConnectionProfilerTest extends TestCase
{
    private function createProfiled(): array
    {
        $inner = Connection::create('sqlite::memory:');
        $store = new InMemoryQueryProfileStore();
        $profiler = new RowcastProfiler($store, new DefaultParameterSanitizer());
        $conn = new ConnectionProfiler($inner, $profiler);

        $conn->executeStatement('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');
        $conn->executeStatement('INSERT INTO t (name) VALUES (?)', ['a']);
        $conn->executeStatement('INSERT INTO t (name) VALUES (?)', ['b']);

        return [$conn, $store];
    }

    public function test_fetch_one_is_profiled(): void
    {
        [$conn, $store] = $this->createProfiled();
        $one = $conn->fetchOne('SELECT name FROM t WHERE id = ?', [1]);

        self::assertSame('a', $one);
        $profiles = $store->getProfiles();
        self::assertCount(4, $profiles);
        $last = $profiles[array_key_last($profiles)];
        self::assertSame('fetchOne', $last->operation);
        self::assertSame(1, $last->rowCount);
    }

    public function test_execute_statement_error_is_profiled(): void
    {
        [$conn, $store] = $this->createProfiled();

        try {
            $conn->executeStatement('NOT SQL');
            self::fail('Expected exception');
        } catch (\Throwable) {
        }

        $last = $store->getProfiles()[array_key_last($store->getProfiles())];
        self::assertNotNull($last->errorClass);
    }

    public function test_to_iterable_finishes_after_iteration(): void
    {
        [$conn, $store] = $this->createProfiled();
        $n = 0;

        foreach ($conn->toIterable('SELECT id FROM t ORDER BY id') as $_) {
            ++$n;
        }

        self::assertSame(2, $n);
        $iterProfiles = array_values(array_filter(
            $store->getProfiles(),
            static fn ($p) => $p->operation === 'toIterable',
        ));
        self::assertCount(1, $iterProfiles);
        self::assertSame(2, $iterProfiles[0]->rowCount);
    }

    public function test_to_iterable_finishes_on_early_break(): void
    {
        [$conn, $store] = $this->createProfiled();

        foreach ($conn->toIterable('SELECT id FROM t ORDER BY id') as $_) {
            break;
        }

        $iterProfiles = array_values(array_filter(
            $store->getProfiles(),
            static fn ($p) => $p->operation === 'toIterable',
        ));
        self::assertSame(1, $iterProfiles[0]->rowCount);
    }

    public function test_transactional_passes_decorator_to_callback(): void
    {
        [$conn, $store] = $this->createProfiled();

        $conn->transactional(function ($c) {
            self::assertInstanceOf(ConnectionProfiler::class, $c);
            $c->fetchOne('SELECT COUNT(*) FROM t');
        });

        $fetchProfiles = array_filter(
            $store->getProfiles(),
            static fn ($p) => $p->operation === 'fetchOne',
        );
        self::assertGreaterThanOrEqual(1, \count($fetchProfiles));
    }

    public function test_create_query_builder_uses_decorator(): void
    {
        [$conn, $store] = $this->createProfiled();
        $conn->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('t')
            ->executeQuery();

        $execProfiles = array_filter(
            $store->getProfiles(),
            static fn ($p) => $p->operation === 'executeQuery',
        );
        self::assertNotEmpty($execProfiles);
    }
}
