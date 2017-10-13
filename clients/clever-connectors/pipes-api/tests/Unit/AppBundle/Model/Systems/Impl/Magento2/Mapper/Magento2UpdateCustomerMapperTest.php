<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Magento2\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class Magento2UpdateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Magento2\Mapper
 */
final class Magento2UpdateCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.magento2-update-customer-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData(
                $this->getRequest('Magento2SingleCustomerItem.json')
            ))->getData(), TRUE
        );

        $this->assertEquals([
            'email'      => 'email@example.com',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ], $response);
    }

}