<?php declare(strict_types=1);

use PipesPhpSdkTests\app\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../../../vendor/autoload.php';

if (filter_input(INPUT_SERVER, 'APP_DEBUG') ?? ((filter_input(INPUT_SERVER, 'APP_ENV') ?? 'dev') !== 'prod')) {
    umask(0_000);
    Debug::enable();
}

// Request::setTrustedProxies(['0.0.0.0/0'], Request::HEADER_FORWARDED);
$kernel   = new Kernel('dev', TRUE);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
