<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler\Tests;

use AsceticSoft\RowcastProfiler\InMemoryQueryProfileStore;
use AsceticSoft\RowcastProfiler\QueryProfile;
use AsceticSoft\RowcastProfiler\SqlStatementType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryQueryProfileStore::class)]
final class InMemoryQueryProfileStoreTest extends TestCase
{
    public function test_get_duplicated_fingerprints(): void
    {
        $store = new InMemoryQueryProfileStore();
        $p = static fn (string $id) => new QueryProfile(
            id: $id,
            operation: 'fetchOne',
            sql: 'SELECT 1',
            sqlNormalized: 'SELECT 1',
            fingerprint: 'select 1',
            statementType: SqlStatementType::Select,
            paramsSanitized: [],
            durationMs: 1.0,
            memoryDeltaBytes: 0,
            rowCount: 1,
            slow: false,
            errorClass: null,
            errorMessage: null,
            context: [],
        );

        $store->record($p('a'));
        $store->record($p('b'));

        $dupes = $store->getDuplicatedFingerprints();

        self::assertCount(1, $dupes);
        self::assertSame('select 1', $dupes[0]['fingerprint']);
        self::assertSame(2, $dupes[0]['count']);
    }

    public function test_max_queries_drops_oldest(): void
    {
        $store = new InMemoryQueryProfileStore(maxQueries: 2);
        $p = static fn (string $id) => new QueryProfile(
            id: $id,
            operation: 'x',
            sql: 'S',
            sqlNormalized: 'S',
            fingerprint: 's',
            statementType: SqlStatementType::Other,
            paramsSanitized: [],
            durationMs: 1.0,
            memoryDeltaBytes: 0,
            rowCount: null,
            slow: false,
            errorClass: null,
            errorMessage: null,
            context: [],
        );

        $store->record($p('1'));
        $store->record($p('2'));
        $store->record($p('3'));

        $ids = array_map(static fn (QueryProfile $p) => $p->id, $store->getProfiles());

        self::assertSame(['2', '3'], $ids);
    }
}
