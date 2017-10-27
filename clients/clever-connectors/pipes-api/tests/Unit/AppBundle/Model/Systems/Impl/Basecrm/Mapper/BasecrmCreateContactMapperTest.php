<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class BasecrmCreateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmCreateContactMapperTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $data = [
            CleverFieldsEnum::EMAIL       => 'eml@eml.com',
            CleverFieldsEnum::FIRST_NAME  => 'first',
            CleverFieldsEnum::LAST_NAME   => 'last',
            CleverFieldsEnum::HARD_BOUNCE => FALSE,
            CleverFieldsEnum::UNSUBSCRIBE => TRUE,
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($data));

        $conn = $this->container->get('hbpf.custom_node.basecrm-create-contact-mapper');
        $res  = $conn->process($dto);

        $expt = [
            'data' => [
                'email'         => 'eml@eml.com',
                'first_name'    => 'first',
                'last_name'     => 'last',
                'custom_fields' => [
                    CleverCustomKeysEnum::UNSUBSCRIBE => TRUE,
                    CleverCustomKeysEnum::HARD_BOUNCE => FALSE,
                ],
            ],
        ];

        self::assertEquals(json_encode($expt), $res->getData());
    }

}