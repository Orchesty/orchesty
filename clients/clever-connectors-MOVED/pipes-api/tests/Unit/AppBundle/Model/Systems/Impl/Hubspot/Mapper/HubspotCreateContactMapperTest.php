<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class HubspotCreateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
final class HubspotCreateContactMapperTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->ownContainer->get('hbpf.custom_node.hubspot-create-contact-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            CleverFieldsEnum::EMAIL      => 'eml@eml.com',
            CleverFieldsEnum::FIRST_NAME => 'first',
            CleverFieldsEnum::LAST_NAME  => 'last',
        ]));

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            'properties' => [
                [
                    'property' => 'email',
                    'value'    => 'eml@eml.com',
                ],
                [
                    'property' => 'firstname',
                    'value'    => 'first',
                ],
                [
                    'property' => 'lastname',
                    'value'    => 'last',
                ],
            ],
        ]), $res->getData());
    }

}