<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 3:05 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector\ZapierSubscriberConnectorAbstract;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZapierSubscriberConnectorAbstractTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
abstract class ZapierSubscriberConnectorAbstractTest extends ConnectorTestCaseAbstract
{

    /**
     * @return ZapierSubscriberConnectorAbstract
     */
    abstract protected function createConnector(): ZapierSubscriberConnectorAbstract;

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $result = Json::decode($this->createConnector()->processEvent(
            (new ProcessDto())->setData($this->getRequest('ZapierWebhookResponse.json'))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([
            'first_name' => 'Karel',
            'last_name'  => 'Barel',
            'email'      => 'karel@barel.com',
            'id'         => '6',
        ], $result);

    }

}