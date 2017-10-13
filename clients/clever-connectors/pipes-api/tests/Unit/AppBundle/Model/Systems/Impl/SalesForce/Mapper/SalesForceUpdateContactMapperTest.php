<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesForce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesForceUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesForce\Mapper
 */
final class SalesForceUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-update-contact-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('SalesForceUpdateContactMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            'email'                      => 'eml@adsf.com',
            'first_name'                 => 'asdasdas',
            'last_name'                  => 'dasdasd',
            CleverFieldsEnum::FOREIGN_ID => '129875625',
        ], $response);
    }

}