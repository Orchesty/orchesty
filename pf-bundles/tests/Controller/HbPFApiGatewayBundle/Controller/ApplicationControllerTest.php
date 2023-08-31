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
use Hanaboso\Utils\String\Json;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApplicationControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController
 */
final class ApplicationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::listOfApplicationsAction
     *
     * @throws Exception
     */
    public function testListApplicationsAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApplicationController/listApplicationsRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationAction
     *
     * @throws Exception
     */
    public function testGetApplicationAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApplicationController/getApplicationRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getUsersApplicationAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationDetailAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::installApplicationAction
     *
     * @throws Exception
     */
    public function testInstallApplicationAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApplicationController/installApplicationRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::updateApplicationSettingsAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::uninstallApplicationAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::saveApplicationPasswordAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::authorizeApplicationAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::authorizeApplicationAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::setAuthorizationTokenAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testGetSynchronousActionsAction(): void
    {
        $apiToken = (new ApiToken())->setKey('abc-123')->setScopes(ApiTokenScopesEnum::cases());
        $dm       = self::getContainer()->get('hbpf.database_manager_locator')->getDm();
        $dm?->persist($apiToken);
        $dm?->flush();
        $this->assertResponse(__DIR__ . '/data/ApplicationController/getSynchronousActionsRequest.json');
        $dm?->getRepository(ApiToken::class)->clear();
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::runSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testRunSynchronousActionsAction(): void
    {
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
