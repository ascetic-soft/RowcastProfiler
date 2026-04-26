<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler\Tests;

use AsceticSoft\RowcastProfiler\DefaultParameterSanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultParameterSanitizer::class)]
final class DefaultParameterSanitizerTest extends TestCase
{
    public function test_it_masks_sensitive_keys(): void
    {
        $s = new DefaultParameterSanitizer();
        $out = $s->sanitize(['email' => 'a@b', 'api_key' => 'secret', 'nested' => ['password' => 'x']], true);

        self::assertSame('a@b', $out['email']);
        self::assertSame('***', $out['api_key']);
        self::assertSame('***', $out['nested']['password']);
    }

    public function test_it_returns_empty_when_collect_params_disabled(): void
    {
        $s = new DefaultParameterSanitizer();
        self::assertSame([], $s->sanitize(['x' => 1], false));
    }

    public function test_it_truncates_long_strings(): void
    {
        $s = new DefaultParameterSanitizer(maxStringLength: 5);
        $out = $s->sanitize(['s' => '123456789'], true);

        self::assertStringContainsString('…', (string) $out['s']);
    }
}
