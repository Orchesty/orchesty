<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class QuickbooksCreatedEventCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
final class QuickbooksCreatedEventCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->ownContainer->get('hbpf.custom_node.quickbooks-created-event-customer-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            'body' => $this->getRequest('CustomerCreated.json'),
        ]))->setHeaders([]);

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            CleverFieldsEnum::EMAIL      => 'eml@emlm.com',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::FIRST_NAME => 'Ichi',
            CleverFieldsEnum::LAST_NAME  => 'Ni',
            CleverFieldsEnum::FOREIGN_ID => '58',
        ]), $res->getData());
    }

}