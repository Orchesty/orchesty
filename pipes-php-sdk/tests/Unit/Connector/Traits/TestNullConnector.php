<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Connector\Traits;

use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait;

/**
 * Class TestNullConnector
 *
 * @package PipesPhpSdkTests\Unit\Connector\Traits
 */
final class TestNullConnector extends ConnectorAbstract
{

    use ProcessActionNotSupportedTrait;
    use ProcessEventNotSupportedTrait;
    use ProcessExceptionTrait;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'null-test-trait';
    }

}
