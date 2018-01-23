<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper\ZapierUpdateSubscriberMapper;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZapierUpdateSubscriberMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierUpdateSubscriberMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $data = [
            CleverFieldsEnum::EMAIL       => 'test@email.com',
            CleverFieldsEnum::FIRST_NAME  => 'Jan',
            CleverFieldsEnum::LAST_NAME   => 'Novak',
            CleverFieldsEnum::FOREIGN_ID  => '20',
            CleverFieldsEnum::UNSUBSCRIBE => FALSE,
            CleverFieldsEnum::HARD_BOUNCE => TRUE,
        ];
        $dto  = new ProcessDto();
        $dto->setData(json_encode($data));

        $connector = new ZapierUpdateSubscriberMapper();
        $connector->process($dto);

        $expectedData = [
            'email'       => 'test@email.com',
            'first_name'  => 'Jan',
            'last_name'   => 'Novak',
            'id'          => '20',
            'unsubscribe' => FALSE,
            'hard_bounce' => TRUE,
        ];

        $this->assertEquals(json_encode($expectedData), $dto->getData());
    }

}