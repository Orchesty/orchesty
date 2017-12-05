<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceCreateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
final class SalesforceCreateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-create-contact-mapper');

        $response = Json::decode($connector->process((new ProcessDto())->setData(Json::encode(
            (new CMSubscriber())
                ->setEmail('email@example.com')
                ->setFirstName('First Name')
                ->setLastName('Last Name')
                ->toArray()
        )))->getData(), TRUE);

        $this->assertEquals([
            'email'     => 'email@example.com',
            'firstName' => 'First Name',
            'lastName'  => 'Last Name',
        ], $response);
    }

    /**
     *
     */
    public function testProcessEventBadRequest(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-create-contact-mapper');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $connector->process((new ProcessDto())->setData('{}'))->getData();
    }

}