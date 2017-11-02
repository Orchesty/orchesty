<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector\BigcommerceUpdatedCustomerConnector;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceUpdatedCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceUpdatedCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $result = Json::decode((new BigcommerceUpdatedCustomerConnector())->processEvent(
            (new ProcessDto())->setData($this->getRequest('BigcommerceWebhookResponse.json'))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals(['id' => 1], $result);
    }

}