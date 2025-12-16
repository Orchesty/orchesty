<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApplicationControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(ApplicationController::class)]
final class ApplicationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetApplicationsAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApplicationController/getApplicationsRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testListApplicationsAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApplicationController/listApplicationsRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApplicationController/getApplicationRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testGetUsersApplicationAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/getUsersApplicationRequest.json',
            [
                'created' => '2010-10-10 10:10:10',
                'id'      => '123456789',
                'updated' => '2010-10-10 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationDetailAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/getApplicationDetailRequest.json',
            [
                'created'       => '2010-10-10 10:10:10',
                'id'            => '123456789',
                'updated'       => '2010-10-10 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testInstallApplicationAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApplicationController/installApplicationRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testUpdateApplicationSettingsAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/updateApplicationSettingsRequest.json',
            [
                'created' => '2010-10-10 10:10:10',
                'id'      => '123456789',
                'updated' => '2010-10-10 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testUninstallApplicationAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/uninstallApplicationRequest.json',
            [
                'created' => '2010-10-10 10:10:10',
                'id'      => '123456789',
                'updated' => '2010-10-10 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testSaveApplicationPasswordAction(): void
    {
        $this->createApplication();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/saveApplicationPasswordRequest.json',
            [
                'created' => '2010-10-10 10:10:10',
                'id'      => '123456789',
                'updated' => '2010-10-10 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testAuthorizeApplicationAction(): void
    {
        $sdk = new Sdk();
        $sdk->setUrl('ip')->setName('name');
        $this->dm->persist($sdk);
        $this->dm->flush();
        $this->dm->clear();

        $curl = $this->createMock(CurlManager::class);
        $curl
            ->method('send')
            ->willReturn(new ResponseDto(200, '', Json::encode(['authorizeUrl' => 'redirect/url']), []));

        $loader = new ServiceLocator(
            $this->dm,
            $curl,
            self::createMock(RedirectInterface::class),
            self::getContainer()->getParameter('backendHost'),
        );

        self::getContainer()->set('hbpp.service.locator', $loader);

        $this->createApplication();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/authorizeApplicationRequest.json',
        );
    }

    /**
     * @throws Exception
     */
    public function testAuthorizeApplicationActionException(): void
    {
        $loader = new ServiceLocator(
            $this->dm,
            self::getContainer()->get('hbpf.transport.curl_manager'),
            self::createMock(RedirectInterface::class),
            self::getContainer()->getParameter('backendHost'),
        );

        self::getContainer()->set('hbpp.service.locator', $loader);

        $this->createApplication();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/authorizeApplicationExRequest.json',
        );
    }

    /**
     * @throws Exception
     */
    public function testSetAuthorizationTokenAction(): void
    {
        $this->createApplication();

        $dto  = new ResponseDto(200, '', Json::encode(['redirectUrl' => 'redirect/url']), []);
        $curl = self::createMock(CurlManager::class);
        $curl->method('send')->willReturn($dto);
        self::getContainer()->set('hbpf.transport.curl_manager', $curl);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/setAuthorizationTokenRequest.json',
            [],
            [],
            [],
            [],
            static function (Response $response): array {
                $response;

                return ['Redirect'];
            },
        );
    }

    /**
     * @throws Exception
     */
    public function testSetAuthorizationTokenQueryAction(): void
    {
        $this->createApplication();

        $dto  = new ResponseDto(200, '', Json::encode(['redirectUrl' => 'redirect/url']), []);
        $curl = self::createMock(CurlManager::class);
        $curl->method('send')->willReturn($dto);
        self::getContainer()->set('hbpf.transport.curl_manager', $curl);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApplicationController/setAuthorizationTokenQueryRequest.json',
            [],
            [],
            [],
            [],
            static function (Response $response): array {
                $response;

                return ['Redirect'];
            },
        );
    }

    /**
     * @throws Exception
     */
    public function testGetSynchronousActionsAction(): void
    {
        self::markTestSkipped();
        $apiToken = (new ApiToken())->setKey('abc-123')->setScopes(ApiTokenScopesEnum::cases());
        $dm       = self::getContainer()->get('hbpf.database_manager_locator')->getDm();
        $dm?->persist($apiToken);
        $dm?->flush();
        $this->assertResponse(__DIR__ . '/data/ApplicationController/getSynchronousActionsRequest.json');
        $dm?->getRepository(ApiToken::class)->clear();
    }

    /**
     * @throws Exception
     */
    public function testRunSynchronousActionsAction(): void
    {
        self::markTestSkipped();
        $apiToken = (new ApiToken())->setKey('abc-123')->setScopes(ApiTokenScopesEnum::cases());
        $dm       = self::getContainer()->get('hbpf.database_manager_locator')->getDm();
        $dm?->persist($apiToken);
        $dm?->flush();
        $this->assertResponse(__DIR__ . '/data/ApplicationController/runSynchronousActionsRequest.json');
        $dm?->getRepository(ApiToken::class)->clear();
    }

    /**
     * @throws Exception
     */
    private function createApplication(): void
    {
        $application = (new ApplicationInstall())->setKey('null')->setUser('orchesty');
        $this->pfd($application);

        $sdk = new Sdk();
        $sdk->setUrl('php-sdk')->setName('php-sdk');
        $this->pfd($sdk);
    }

}
