<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceUpdatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
final class SalesforceUpdatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->ownContainer->get('hbpf.custom_node.salesforce-updated-contact-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData($this->getRequest('SalesforceUpdatedContactMapper.json')))->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'email@example.com',
            CleverFieldsEnum::FIRST_NAME => 'First Name',
            CleverFieldsEnum::LAST_NAME  => 'Last Name',
            CleverFieldsEnum::FOREIGN_ID => '123456789',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessEventBadRequest(): void
    {
        $connector = $this->ownContainer->get('hbpf.custom_node.salesforce-updated-contact-mapper');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $connector->process((new ProcessDto())->setData('{}'))->getData();
    }

}