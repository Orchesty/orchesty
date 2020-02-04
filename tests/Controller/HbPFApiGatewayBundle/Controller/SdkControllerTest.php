<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class SdkControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\SdkController
 */
final class SdkControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\SdkController::getAllAction
     *
     * @throws Exception
     */
    public function testGetAllAction(): void
    {
        $this->createSdk();

        $this->assertResponse(__DIR__ . '/data/SdkController/getAllRequest.json', ['id' => '123456789']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\SdkController::getOneAction
     *
     * @throws Exception
     */
    public function testGetOneAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/SdkController/getOneRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createSdk()->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\SdkController::createAction
     *
     * @throws Exception
     */
    public function testCreateAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/SdkController/createRequest.json', ['id' => '123456789']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\SdkController::updateAction
     *
     * @throws Exception
     */
    public function testUpdateAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/SdkController/updateRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createSdk()->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\SdkController::deleteAction
     *
     * @throws Exception
     */
    public function testDeleteAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/SdkController/deleteRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createSdk()->getId()]
        );
    }

    /**
     * @return Sdk
     * @throws Exception
     */
    private function createSdk(): Sdk
    {
        $sdk = new Sdk();

        $this->pfd($sdk);

        return $sdk;
    }

}
