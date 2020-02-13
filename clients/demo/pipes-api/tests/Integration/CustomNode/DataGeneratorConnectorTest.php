<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\DataGeneratorConnector;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\String\Json;

/**
 * Class DataGeneratorConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class DataGeneratorConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\DataGeneratorConnector::process
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var DataGeneratorConnector $connector */
        $connector = self::$container->get('hbpf.custom_node.data-generator');

        $dto = $connector->process(new ProcessDto());

        self::assertArrayHasKey('generator', Json::decode($dto->getData()));
    }

}
