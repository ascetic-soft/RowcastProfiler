<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

interface ParameterSanitizerInterface
{
    /**
     * @param array<string|int, mixed> $params
     * @return array<string|int, mixed>
     */
    public function sanitize(array $params, bool $collectParams): array;
}
