<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

final class InMemoryQueryProfileStore implements QueryProfileStoreInterface
{
    /** @var list<QueryProfile> */
    private array $profiles = [];

    public function __construct(
        private int $maxQueries = 500,
    ) {
    }

    public function record(QueryProfile $profile): void
    {
        if ($this->maxQueries <= 0) {
            return;
        }

        $this->profiles[] = $profile;

        if (\count($this->profiles) > $this->maxQueries) {
            array_shift($this->profiles);
        }
    }

    public function getProfiles(): array
    {
        return $this->profiles;
    }

    public function reset(): void
    {
        $this->profiles = [];
    }

    public function getDuplicatedFingerprints(): array
    {
        /** @var array<string, array{count: int, total_duration_ms: float}> $byFp */
        $byFp = [];

        foreach ($this->profiles as $p) {
            if (!isset($byFp[$p->fingerprint])) {
                $byFp[$p->fingerprint] = ['count' => 0, 'total_duration_ms' => 0.0];
            }

            ++$byFp[$p->fingerprint]['count'];
            $byFp[$p->fingerprint]['total_duration_ms'] += $p->durationMs;
        }

        $dupes = [];

        foreach ($byFp as $fingerprint => $agg) {
            if ($agg['count'] > 1) {
                $dupes[] = [
                    'fingerprint' => $fingerprint,
                    'count' => $agg['count'],
                    'total_duration_ms' => $agg['total_duration_ms'],
                ];
            }
        }

        usort($dupes, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return $dupes;
    }

    public function setMaxQueries(int $maxQueries): void
    {
        $this->maxQueries = $maxQueries;
    }
}
