<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceDeletedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
final class SalesforceDeletedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-deleted-contact-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData($this->getRequest('SalesforceDeletedContactMapper.json')))->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL       => 'email@example.com',
            CleverFieldsEnum::FOREIGN_ID  => '123456789',
            CleverFieldsEnum::REACTIVATE  => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessEventBadRequest(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-deleted-contact-mapper');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $connector->process((new ProcessDto())->setData('{}'))->getData();
    }

}