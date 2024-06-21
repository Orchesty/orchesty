<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Repository;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class ApplicationInstallRepositoryTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Repository
 */
#[CoversClass(ApplicationInstallRepository::class)]
final class ApplicationInstallRepositoryTest extends KernelTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testFindUserAppErr(): void
    {
        $this->privateSetUp();

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["user"],"users":["key"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([])),
            ),
        );

        /** @var ApplicationInstallRepository $appInstallRepository */
        $appInstallRepository = self::getContainer()->get('hbpf.application_install.repository');

        self::expectException(ApplicationInstallException::class);
        $appInstallRepository->findUserApp('user', 'key');
    }

    /**
     * @return void
     */
    private function privateSetUp(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
    }

}
