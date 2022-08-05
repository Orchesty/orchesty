<?php declare(strict_types=1);

if(!getenv('METRICS_ODM_DSN')){
    $service = getenv('METRICS_SERVICE') ?? 'influx';
    $dsn = $service === 'influx' ? getenv('MONGODB_DSN') : getenv('METRICS_DSN');
    putenv(sprintf('METRICS_ODM_DSN=%s', $dsn));
}
