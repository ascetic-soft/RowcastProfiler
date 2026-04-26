<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler\Tests;

use AsceticSoft\RowcastProfiler\SqlClassifier;
use AsceticSoft\RowcastProfiler\SqlStatementType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqlClassifier::class)]
final class SqlClassifierTest extends TestCase
{
    public function test_classify_and_fingerprint(): void
    {
        $c = new SqlClassifier();
        $sql = "  SELECT  *  FROM  t  ";

        self::assertSame(SqlStatementType::Select, $c->classify($sql));
        self::assertSame('select * from t', $c->fingerprint($sql));
        self::assertSame('SELECT * FROM t', $c->normalize($sql));
    }
}
