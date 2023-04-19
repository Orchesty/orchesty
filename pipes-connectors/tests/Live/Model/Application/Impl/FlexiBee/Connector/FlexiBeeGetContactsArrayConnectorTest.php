<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\FlexiBee\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class FlexiBeeGetContactsArrayConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeGetContactsArrayConnectorTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @throws Exception
     */
    public function testGetContactsArray(): void
    {
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["flexibee"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($this->getAppInstall()->toArray())),
            ),
        );

        $conn = self::getContainer()->get('hbpf.connector.flexibee.get-contacts-array');
        $conn->setApplication($this->mockApplication());
        $dto = DataProvider::getProcessDto($this->getApp()->getName(), 'user');
        $conn->processAction($dto);

        self::assertFake();
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
    }

    /**
     * @return FlexiBeeApplication
     */
    private function getApp(): FlexiBeeApplication
    {
        return self::getContainer()->get('hbpf.application.flexibee');
    }

    /**
     * @throws Exception
     */
    private function getAppInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getName());

        $appInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM =>
                    [
                        'auth'        => 'http',
                        'flexibeeUrl' => 'https://demo.flexibee.eu/c/demo',
                        'password' => 'winstrom',
                        'user'     => 'winstrom',
                    ],
            ],
        );

        return $appInstall;
    }

    /**
     * @return FlexiBeeApplication
     */
    private function mockApplication(): FlexiBeeApplication
    {
        $app = self::createPartialMock(FlexiBeeApplication::class, ['getRequestDto']);
        $app->method('getRequestDto')->willReturnCallback(
            static function (
                ProcessDtoAbstract $dto,
                ApplicationInstall $applicationInstall,
                string $method,
                ?string $url = NULL,
                ?string $data = NULL,
            ): RequestDto {

                $applicationInstall;
                $request = new RequestDto(new Uri(sprintf('%s', ltrim($url ?? '', '/'))), $method, $dto);

                $request->setHeaders(
                    [
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                );

                if (isset($data)) {
                    $request->setBody($data);
                }

                return $request;
            },
        );

        return $app;
    }

}
