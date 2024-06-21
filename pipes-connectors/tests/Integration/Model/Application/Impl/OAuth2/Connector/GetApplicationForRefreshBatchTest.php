<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector\GetApplicationForRefreshBatchConnector;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class GetApplicationForRefreshBatchTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\OAuth2\Connector
 */
#[CoversClass(GetApplicationForRefreshBatchConnector::class)]
final class GetApplicationForRefreshBatchTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     * @throws GuzzleException
     * @throws DateTimeException
     */
    public function testProcessAction(): void
    {
        self::markTestSkipped('Mock Date properly');

        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"expires":1677137981}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    '[{}]',
                ),
            ),
        );

        /** @var GetApplicationForRefreshBatchConnector $conn */
        $conn = self::getContainer()->get('hbpf.batch.batch-get_application_for_refresh');

        $dto = $conn->processAction(new BatchProcessDto());
        self::assertCount(1, Json::decode($dto->getBridgeData()));
    }

    /**
     * @return void
     */
    public function testGetName(): void
    {
        $application = new GetApplicationForRefreshBatchConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );

        self::assertEquals('get_application_for_refresh', $application->getName());
    }

}
