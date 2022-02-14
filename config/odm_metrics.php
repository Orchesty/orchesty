<?php
$service = getenv('METRICS_SERVICE') ?? 'influx';

$dsn = $service === 'influx' ? getenv('MONGODB_DSN') : getenv('METRICS_DSN');
$container->setParameter('odm_metrics_dsn', $dsn);