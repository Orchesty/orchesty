<?php declare(strict_types=1);

use Hanaboso\PipesPhpSdk\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$kernel = new Kernel((string) ($_SERVER['APP_ENV'] ?? 'test'), (bool) ($_SERVER['APP_DEBUG'] ?? TRUE));
$kernel->boot();

return $kernel->getContainer()->get('doctrine_mongodb.odm.document_manager');
