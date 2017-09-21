<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriptionConnector;

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 21.9.17
 * Time: 17:49
 */

use CleverConnectors\AppBundle\Exceptions\Exception;
use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class CMSubscriptionConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriptionConnector
 */
abstract class CMSubscriptionConnectorAbstract extends CMAuthorization implements ConnectorInterface
{

    /**
     * @param array $data
     *
     * @return ProcessDto|void
     * @throws Exception
     */
    public function processEvent(array $data): ProcessDto
    {
        throw new Exception('CMSubscriptionConnector has no support for webhooks!');
    }

}