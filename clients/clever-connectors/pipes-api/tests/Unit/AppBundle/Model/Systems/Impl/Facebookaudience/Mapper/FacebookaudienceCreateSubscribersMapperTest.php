<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Mapper\FacebookaudienceCreateSubscribersMapper;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceCreateSubscribersMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Facebookaudience\Mapper
 */
final class FacebookaudienceCreateSubscribersMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers FacebookaudienceCreateSubscribersMapper::process()
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.facebookaudience-create-subscribers-mapper');

        $data = [
            ['email' => 'aaa@bbb.com'],
            ['email' => 'abc@abc.com'],
        ];

        $dto = new ProcessDto();
        $dto->setData(Json::encode($data));

        $result = $connector->process($dto);

        $this->assertEquals([
            'payload' => [
                'schema' => 'EMAIL_SHA256',
                'data'   => [
                    hash('sha256', 'aaa@bbb.com'),
                    hash('sha256', 'abc@abc.com'),
                ],
            ],
        ], Json::decode($result->getData(), TRUE));
    }

    /**
     * @covers FacebookaudienceCreateSubscribersMapper::process()
     */
    public function testProcessMissingData(): void
    {
        $connector = $this->container->get('hbpf.custom_node.facebookaudience-create-subscribers-mapper');

        $dto = new ProcessDto();
        $dto->setData(Json::encode([]));

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $connector->process($dto);
    }

}