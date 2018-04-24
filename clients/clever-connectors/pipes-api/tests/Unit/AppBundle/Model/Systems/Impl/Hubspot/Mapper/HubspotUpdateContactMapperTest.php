<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class HubspotUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
final class HubspotUpdateContactMapperTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->container->get('hbpf.custom_node.hubspot-update-contact-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            CleverFieldsEnum::FOREIGN_ID => '123456',
        ]))->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
        ]);

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            'id'   => '123456',
            'body' => json_encode([
                'properties' => [
                    [
                        'property' => CleverCustomKeysEnum::UNSUBSCRIBE,
                        'value'    => TRUE,
                    ],
                ],
            ]),
        ]), $res->getData());
    }

}