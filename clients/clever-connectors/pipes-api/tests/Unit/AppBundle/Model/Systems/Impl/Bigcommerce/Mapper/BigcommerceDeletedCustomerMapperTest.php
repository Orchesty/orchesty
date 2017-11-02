<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceDeletedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
final class BigcommerceDeletedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.bigcommerce-deleted-customer-mapper');

        $response = Json::decode($connector->process((new ProcessDto())->setData('{"id":1}'))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => '1',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => FALSE,
        ], $response);
    }

}