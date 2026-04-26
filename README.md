# Rowcast Profiler

Lightweight SQL profiler for [Rowcast](https://github.com/ABorodulin/rowcast): wraps `AsceticSoft\Rowcast\ConnectionInterface` to record timings, parameters (sanitized), errors, and simple aggregates.

## Install

```bash
composer require ascetic-soft/rowcast-profiler
```

## Usage

```php
use AsceticSoft\Rowcast\Connection;
use AsceticSoft\RowcastProfiler\ConnectionProfiler;
use AsceticSoft\RowcastProfiler\InMemoryQueryProfileStore;
use AsceticSoft\RowcastProfiler\DefaultParameterSanitizer;
use AsceticSoft\RowcastProfiler\RowcastProfiler;

$inner = Connection::create('sqlite::memory:');
$store = new InMemoryQueryProfileStore();
$sanitizer = new DefaultParameterSanitizer();
$profiler = new RowcastProfiler($store, $sanitizer, slowQueryThresholdMs: 50.0, maxQueries: 500);

$connection = new ConnectionProfiler($inner, $profiler);
$connection->fetchOne('SELECT 1');

foreach ($store->getProfiles() as $profile) {
    echo $profile->sql, ' ', $profile->durationMs, "ms\n";
}
```

Symfony: enable `rowcast.profiler` in [RowcastBundle](https://github.com/ABorodulin/rowcast-bundle) (see bundle docs).

## Packagist / versioning

The root `composer.json` may include a `"version": "1.0.0"` field so path repositories and CI resolve a stable `^1.0` constraint. Remove that field when tagging releases on GitHub/Packagist (tags define the version).

## License

MIT
