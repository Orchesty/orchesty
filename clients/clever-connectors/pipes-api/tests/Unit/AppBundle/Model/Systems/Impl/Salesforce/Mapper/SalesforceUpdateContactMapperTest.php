<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
final class SalesforceUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-update-contact-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('SalesforceUpdateContactMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'eml@adsf.com',
            CleverFieldsEnum::FIRST_NAME => 'asdasdas',
            CleverFieldsEnum::LAST_NAME  => 'dasdasd',
            CleverFieldsEnum::FOREIGN_ID => '129875625',
            CleverFieldsEnum::REACTIVATE => TRUE,
        ], $response);
    }

}