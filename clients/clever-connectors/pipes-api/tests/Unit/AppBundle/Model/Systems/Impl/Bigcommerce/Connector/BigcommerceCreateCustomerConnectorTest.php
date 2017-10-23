<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector\BigcommerceCreateCustomerConnector;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceCreateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceCreateCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $result = Json::decode((new BigcommerceCreateCustomerConnector())->processEvent(
            (new ProcessDto())->setData($this->getRequest('BigcommerceWebhookResponse.json'))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals(['id' => 1], $result);
    }

}