<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceDeleteContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
final class SalesforceDeleteContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-delete-contact-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('SalesforceDeleteContactMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            'email'                      => 'eml@adsf.com',
            CleverFieldsEnum::FOREIGN_ID => '129875625',
        ], $response);
    }

}