<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

interface QueryProfileStoreInterface
{
    public function record(QueryProfile $profile): void;

    /**
     * @return list<QueryProfile>
     */
    public function getProfiles(): array;

    public function reset(): void;

    /**
     * Duplicated fingerprints (count > 1), sorted by count descending.
     *
     * @return list<array{fingerprint: string, count: int, total_duration_ms: float}>
     */
    public function getDuplicatedFingerprints(): array;
}
