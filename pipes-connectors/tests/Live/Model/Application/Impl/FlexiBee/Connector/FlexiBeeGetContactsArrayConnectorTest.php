<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\FlexiBee\Connector;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FlexiBeeGetContactsArrayConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeGetContactsArrayConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetContactsArray(): void
    {
        $this->getAppInstall();

        $conn = self::$container->get('hbpf.connector.flexibee.get-contacts-array');
        $conn->setApplication($this->mockApplication());
        $dto = DataProvider::getProcessDto($this->getApp()->getKey(), 'user');
        $conn->processAction($dto);
        self::assertFake();
    }

    /**
     * @return FlexiBeeApplication
     */
    private function getApp(): FlexiBeeApplication
    {
        return self::$container->get('hbpf.application.flexibee');
    }

    /**
     * @throws Exception
     */
    private function getAppInstall(): void
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getKey());

        $appInstall->setSettings(
            [
                FlexiBeeApplication::AUTHORIZATION_SETTINGS =>
                    [
                        'user'     => 'winstrom',
                        'password' => 'winstrom',
                    ],
                BasicApplicationAbstract::FORM              =>
                    [
                        'auth'        => 'http',
                        'flexibeeUrl' => 'https://demo.flexibee.eu/c/demo',
                    ],
            ],
        );

        $this->pfd($appInstall);
    }

    /**
     * @return FlexiBeeApplication
     */
    private function mockApplication(): FlexiBeeApplication
    {
        $app = self::createPartialMock(FlexiBeeApplication::class, ['getRequestDto']);
        $app->method('getRequestDto')->willReturnCallback(
            static function (
                ApplicationInstall $applicationInstall,
                string $method,
                ?string $url = NULL,
                ?string $data = NULL,
            ): RequestDto {

                $applicationInstall;
                $request = new RequestDto($method, new Uri(sprintf('%s', ltrim($url ?? '', '/'))));

                $request->setHeaders(
                    [
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
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
