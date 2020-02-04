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
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getUsersApplicationAction
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
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationDetailAction
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
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::installApplicationAction
     */
    public function testInstallApplicationAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ApplicationController/installApplicationRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::updateApplicationSettingsAction
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
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::uninstallApplicationAction
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
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::saveApplicationPasswordAction
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
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::authorizeApplicationAction
     */
    public function testAuthorizeApplicationAction(): void
    {
        $this->createApplication();

        $this->assertResponse(__DIR__ . '/data/ApplicationController/authorizeApplicationRequest.json');
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::setAuthorizationTokenAction
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
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
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
