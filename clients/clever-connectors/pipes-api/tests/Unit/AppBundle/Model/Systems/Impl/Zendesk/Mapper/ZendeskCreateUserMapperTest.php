<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ZendeskCreateUserMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
final class ZendeskCreateUserMapperTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->container->get('hbpf.custom_node.zendesk-create-user-mapper');

        $dto = new ProcessDto();
        $dto->setData(json_encode([
            CleverFieldsEnum::EMAIL      => 'eml@eml.com',
            CleverFieldsEnum::FIRST_NAME => 'first',
            CleverFieldsEnum::LAST_NAME  => 'last',
        ]));

        /** @var ProcessDto $res */
        $res = $mapper->process($dto);

        self::assertEquals(json_encode([
            'user' => [
                'email' => 'eml@eml.com',
                'name'  => 'first last',
            ],
        ]), $res->getData());
    }

}