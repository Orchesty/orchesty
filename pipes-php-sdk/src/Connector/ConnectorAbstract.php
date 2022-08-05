<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Connector;

use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;

/**
 * Class ConnectorAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Connector
 */
abstract class ConnectorAbstract extends CommonNodeAbstract implements ConnectorInterface
{

    use ConnectorTrait;

}
