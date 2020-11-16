<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
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
        $this->assertResponse(__DIR__ . '/data/ApplicationController/listApplicationsRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationAction
     *
     * @throws Exception
     */
    public function testGetApplicationAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ApplicationController/getApplicationRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getUsersApplicationAction
     *
     * @throws Exception
     */
    public function testGetUsersApplicationAction(): void
    {
        $this->createApplication();

        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/getUsersApplicationRequest.json',
            [
                'id'      => '123456789',
                'created' => '2010-10-10 10:10:10',
                'updated' => '2010-10-10 10:10:10',
            ]
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

        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/getApplicationDetailRequest.json',
            [
                'id'      => '123456789',
                'created' => '2010-10-10 10:10:10',
                'updated' => '2010-10-10 10:10:10',
            ]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::installApplicationAction
     *
     * @throws Exception
     */
    public function testInstallApplicationAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ApplicationController/installApplicationRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::updateApplicationSettingsAction
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettingsAction(): void
    {
        $this->createApplication();

        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/updateApplicationSettingsRequest.json',
            [
                'id'      => '123456789',
                'created' => '2010-10-10 10:10:10',
                'updated' => '2010-10-10 10:10:10',
            ]
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

        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/uninstallApplicationRequest.json',
            [
                'id'      => '123456789',
                'created' => '2010-10-10 10:10:10',
                'updated' => '2010-10-10 10:10:10',
            ]
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

        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/saveApplicationPasswordRequest.json',
            [
                'id'      => '123456789',
                'created' => '2010-10-10 10:10:10',
                'updated' => '2010-10-10 10:10:10',
            ]
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
        $sdk->setKey('ip')->setValue('name');
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
            self::createMock(RedirectInterface::class)
        );

        self::$container->set('hbpp.service.locator', $loader);

        $this->createApplication();

        $this->assertResponse(__DIR__ . '/data/ApplicationController/authorizeApplicationRequest.json');
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
            self::$container->get('hbpf.transport.curl_manager'),
            self::createMock(RedirectInterface::class)
        );

        self::$container->set('hbpp.service.locator', $loader);

        $this->createApplication();

        $this->assertResponse(__DIR__ . '/data/ApplicationController/authorizeApplicationExRequest.json');
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
        self::$container->set('hbpf.transport.curl_manager', $curl);

        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/setAuthorizationTokenRequest.json',
            [],
            [],
            [],
            [],
            static function (Response $response): array {
                $response;

                return ['Redirect'];
            }
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
        self::$container->set('hbpf.transport.curl_manager', $curl);

        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/setAuthorizationTokenQueryRequest.json',
            [],
            [],
            [],
            [],
            static function (Response $response): array {
                $response;

                return ['Redirect'];
            }
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::applicationStatisticsAction
     *
     * @throws Exception
     */
    public function testApplicationStatisticsAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/applicationStatisticsRequest.json',
            [],
            [':key' => 'superApp']
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::userStatisticsAction
     *
     * @throws Exception
     */
    public function testUserStatisticsAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/ApplicationController/userStatisticsRequest.json',
            [],
            [':user' => '123-456-789']
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testGetSynchronousActionsAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ApplicationController/getSynchronousActionsRequest.json',);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::runSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testRunSynchronousActionsAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ApplicationController/runSynchronousActionsRequest.json',);
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplication(): ApplicationInstall
    {
        $application = (new ApplicationInstall())->setKey('null')->setUser('user');
        $this->pfd($application);

        $sdk = new Sdk();
        $sdk->setKey('php-sdk')->setValue('php-sdk');
        $this->pfd($sdk);

        return $application;
    }

}
