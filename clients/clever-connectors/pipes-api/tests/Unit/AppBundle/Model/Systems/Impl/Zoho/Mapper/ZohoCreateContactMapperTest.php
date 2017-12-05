<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoCreateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
final class ZohoCreateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zoho-create-contact-mapper');

        $response = Json::decode($connector->process((new ProcessDto())->setData(Json::encode(
            (new CMSubscriber())
                ->setEmail('email@example.com')
                ->setFirstName('First Name')
                ->setLastName('Last Name')
                ->toArray()
        )))->getData(), TRUE);

        $this->assertEquals([
            'xml' => "<Contacts><row no='1'><FL val='Email'>email@example.com</FL><FL val='First Name'>First Name</FL><FL val='Last Name'>Last Name</FL></row></Contacts>",
        ], $response);
    }

    /**
     *
     */
    public function testProcessEventBadRequest(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zoho-create-contact-mapper');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $connector->process((new ProcessDto())->setData('{}'))->getData();
    }

}