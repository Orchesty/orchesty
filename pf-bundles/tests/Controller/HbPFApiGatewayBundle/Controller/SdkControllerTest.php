<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\SdkController;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class SdkControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(SdkController::class)]
final class SdkControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetAllAction(): void
    {
        $this->createSdk();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/SdkController/getAllRequest.json',
            ['id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOneAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/SdkController/getOneRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createSdk()->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/SdkController/createRequest.json',
            ['id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/SdkController/updateRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createSdk()->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/SdkController/deleteRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createSdk()->getId()],
        );
    }

    /**
     * @return Sdk
     * @throws Exception
     */
    private function createSdk(): Sdk
    {
        $sdk = new Sdk();
        $sdk
            ->setName('key')
            ->setUrl('val')
            ->setHeaders([]);

        $this->pfd($sdk);

        return $sdk;
    }

}
