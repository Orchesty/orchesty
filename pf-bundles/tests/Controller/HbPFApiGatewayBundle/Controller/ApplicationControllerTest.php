<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
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
     */
    public function testListApplicationsAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ApplicationController/listApplicationsRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationAction
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
        $this->createApplication();

        $this->assertResponse(__DIR__ . '/data/ApplicationController/authorizeApplicationRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::setAuthorizationTokenAction
     *
     * @throws Exception
     */
    public function testSetAuthorizationTokenAction(): void
    {
        $this->createApplication();

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
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplication(): ApplicationInstall
    {
        $application = (new ApplicationInstall())->setKey('null')->setUser('user');

        $this->pfd($application);

        return $application;
    }

}
