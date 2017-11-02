<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class BigCommerceCreateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
final class BigCommerceCreateCustomerMapperTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->container->get('hbpf.custom_node.bigcommerce-create-customer-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            CleverFieldsEnum::EMAIL      => 'eml@eml.com',
            CleverFieldsEnum::FIRST_NAME => 'ichi',
            CleverFieldsEnum::LAST_NAME  => 'ni',
        ]));

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            'email'      => 'eml@eml.com',
            'first_name' => 'ichi',
            'last_name'  => 'ni',
        ]), $res->getData());
    }

}