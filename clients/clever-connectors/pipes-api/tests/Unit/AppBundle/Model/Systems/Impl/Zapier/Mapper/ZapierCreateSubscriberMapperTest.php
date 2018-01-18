<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper\ZapierCreateSubscriberMapper;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZapierCreateSubscriberMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierCreateSubscriberMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $data = [
            CleverFieldsEnum::EMAIL      => 'test@email.com',
            CleverFieldsEnum::FIRST_NAME => 'Jan',
            CleverFieldsEnum::LAST_NAME  => 'Novak',
            CleverFieldsEnum::FOREIGN_ID => '20',
        ];
        $dto  = new ProcessDto();
        $dto->setData(json_encode($data));

        $connector = new ZapierCreateSubscriberMapper();
        $connector->process($dto);

        $expectedData = [
            'email'       => 'test@email.com',
            'first_name'  => 'Jan',
            'last_name'   => 'Novak',
            'id'          => '20',
            'unsubscribe' => FALSE,
            'hard_bounce' => FALSE,
        ];

        $this->assertEquals(json_encode($expectedData), $dto->getData());
    }

}