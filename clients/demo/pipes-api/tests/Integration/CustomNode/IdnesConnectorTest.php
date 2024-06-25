<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\Connector\IdnesConnector;
use DemoTests\KernelTestCaseAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class IdnesConnectorTest
 *
 * @package DemoTests\Integration\CustomNode
 */
#[CoversClass(IdnesConnector::class)]
final class IdnesConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @throws CurlException
     */
    public function testProcess(): void
    {
        /** @var IdnesConnector $connector */
        $connector = self::getContainer()->get('hbpf.connector.idnes-connector');

        $dto = $connector->processAction((new ProcessDto())->setData('{}'));
        self::assertEquals('{}', $dto->getData());
    }

}
