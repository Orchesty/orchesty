<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class QuickbooksCreatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
final class QuickbooksCreatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->container->get('hbpf.custom_node.quickbooks-created-customer-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            'body' => $this->getRequest('CustomerCreated.json'),
        ]))->setHeaders([]);

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            CleverFieldsEnum::EMAIL      => 'eml@emlm.com',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::FIRST_NAME => 'Ichi',
            CleverFieldsEnum::LAST_NAME  => 'Ni',
            CleverFieldsEnum::FOREIGN_ID => '58',
        ]), $res->getData());
    }

}