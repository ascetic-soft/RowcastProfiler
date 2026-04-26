<?php

declare(strict_types=1);

namespace AsceticSoft\RowcastProfiler;

final class DefaultParameterSanitizer implements ParameterSanitizerInterface
{
    private const string MASK = '***';

    /**
     * @param list<string> $extraSensitiveKeySubstrings case-insensitive substrings for array keys / named params
     */
    public function __construct(
        private int $maxStringLength = 500,
        private int $maxArrayItems = 20,
        private int $maxNestingDepth = 3,
        private array $extraSensitiveKeySubstrings = [],
    ) {
    }

    public function sanitize(array $params, bool $collectParams): array
    {
        if (!$collectParams) {
            return [];
        }

        $out = [];

        foreach ($params as $key => $value) {
            $out[$key] = $this->sanitizeValue($value, 0, (string) $key);
        }

        return $out;
    }

    private function sanitizeValue(mixed $value, int $depth, string $keyContext): mixed
    {
        if ($depth > $this->maxNestingDepth) {
            return '[max-depth]';
        }

        if ($this->isSensitiveKey($keyContext)) {
            return self::MASK;
        }

        if ($value === null || \is_bool($value) || \is_int($value) || \is_float($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $len = strlen($value);

            if ($len > $this->maxStringLength) {
                return substr($value, 0, $this->maxStringLength) . '…[' . $len . ' chars]';
            }

            return $value;
        }

        if ($value instanceof \Stringable) {
            return $this->sanitizeValue((string) $value, $depth, $keyContext);
        }

        if (\is_array($value)) {
            $items = array_slice($value, 0, $this->maxArrayItems, true);
            $result = [];

            foreach ($items as $k => $v) {
                $childKey = $keyContext . '.' . $k;
                $result[$k] = $this->sanitizeValue($v, $depth + 1, $childKey);
            }

            if (\count($value) > $this->maxArrayItems) {
                $result['…'] = '[' . \count($value) . ' items total]';
            }

            return $result;
        }

        return '[' . get_debug_type($value) . ']';
    }

    private function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);
        $needles = array_merge(
            ['password', 'passwd', 'token', 'secret', 'api_key', 'apikey', 'authorization', 'auth', 'credential'],
            $this->extraSensitiveKeySubstrings,
        );

        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($lower, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
