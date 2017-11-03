<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper\QuickbooksCreateCustomerMapper;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class QuickbooksCreateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
final class QuickbooksCreateCustomerMapperTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->container->get('hbpf.custom_node.quickbooks-create-customer-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            CleverFieldsEnum::LAST_NAME  => 'last',
            CleverFieldsEnum::EMAIL      => 'eml',
            CleverFieldsEnum::FIRST_NAME => 'namae',
        ]));

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            'body'                                  => json_encode([
                'PrimaryEmailAddr' => [
                    'Address' => 'eml',
                ],
                'GivenName'        => 'namae',
                'FamilyName'       => 'last',
            ]),
            QuickbooksCreateCustomerMapper::SUCCESS => FALSE,
            QuickbooksCreateCustomerMapper::ATTEMPT => FALSE,
        ]), $res->getData());
    }

}