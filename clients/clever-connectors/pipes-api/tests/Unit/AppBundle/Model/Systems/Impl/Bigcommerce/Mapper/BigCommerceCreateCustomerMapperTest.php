<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
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
        $mapper = $this->ownContainer->get('hbpf.custom_node.bigcommerce-create-customer-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
        ]));

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            'email'      => 'User01@User01.com',
            'first_name' => 'User01',
            'last_name'  => 'User01',
        ]), $res->getData());
    }

}