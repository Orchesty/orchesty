<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class BasecrmUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmUpdateContactMapperTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $data = [
            CleverFieldsEnum::FOREIGN_ID => 'ids',
        ];

        $dto = new ProcessDto();
        $dto->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE,
        ])->setData(json_encode($data));

        $res = $this->container->get('hbpf.custom_node.basecrm-update-contact-mapper')->process($dto);
        self::assertEquals(json_encode([
            'data' => [
                'custom_fields' => [
                    CleverCustomKeysEnum::HARD_BOUNCE => TRUE,
                ],
            ],
        ]), json_decode($res->getData())->body);
    }

}