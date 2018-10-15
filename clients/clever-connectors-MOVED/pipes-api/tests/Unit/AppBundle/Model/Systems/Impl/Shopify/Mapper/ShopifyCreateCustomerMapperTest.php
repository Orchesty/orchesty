<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ShopifyCreateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
final class ShopifyCreateCustomerMapperTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->ownContainer->get('hbpf.custom_node.shopify-create-customer-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            CleverFieldsEnum::EMAIL      => 'eml@eml.com',
            CleverFieldsEnum::FIRST_NAME => 'first',
            CleverFieldsEnum::LAST_NAME  => 'last',
        ]));

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            'customer' => [
                'email'      => 'eml@eml.com',
                'first_name' => 'first',
                'last_name'  => 'last',
            ],
        ]), $res->getData());
    }

}