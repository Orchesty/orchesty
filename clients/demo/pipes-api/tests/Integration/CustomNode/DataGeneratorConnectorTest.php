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
     * @covers \Demo\CustomNode\DataGeneratorConnector::processAction
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var DataGeneratorConnector $connector */
        $connector = self::getContainer()->get('hbpf.custom_node.data-generator');

        $dto = $connector->processAction((new ProcessDto())->setData('{}'));

        self::assertArrayHasKey('generator', Json::decode($dto->getData()));
    }

}
