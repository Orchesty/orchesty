<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector\BigcommerceDeletedCustomerConnector;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceDeletedCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceDeletedCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $result = Json::decode((new BigcommerceDeletedCustomerConnector())->processEvent(
            (new ProcessDto())->setData($this->getRequest('BigcommerceWebhookResponse.json'))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals(['id' => 1], $result);
    }

}