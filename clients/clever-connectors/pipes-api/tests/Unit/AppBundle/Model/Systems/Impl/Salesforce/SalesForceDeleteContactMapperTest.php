<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesForceDeleteContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce
 */
class SalesForceDeleteContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-contact-delete-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData(file_get_contents(__DIR__ . '/data/SalesForceSingleDeletedContactItem.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            'email'                      => 'eml@adsf.com',
            CleverFieldsEnum::FOREIGN_ID => '129875625',
        ], $response);
    }

}