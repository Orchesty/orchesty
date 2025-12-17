<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Configurator\Model\SdkManager;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class SdkControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 */
#[CoversClass(SdkController::class)]
#[CoversClass(SdkHandler::class)]
#[CoversClass(SdkManager::class)]
final class SdkControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createSdk('One');
        $this->createSdk('Two');

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Sdk/getAllRequest.json',
            ['id' => '5e32a7a41ffeab2445696983'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Sdk/getOneRequest.json',
            ['id' => '5e32a9b8a1b2a70fef6fa273'],
            [':id' => $this->createSdk('One')->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOneNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Sdk/getOneNotFoundRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Sdk/createRequest.json',
            ['id' => '5e32aab74c2bd32924205303'],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateErr(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Sdk/createErrRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Sdk/updateRequest.json',
            ['id' => '5e32ac41505d6e1b5047eb43'],
            [':id' => $this->createSdk('One')->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Sdk/updateNotFoundRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Sdk/deleteRequest.json',
            ['id' => '5e32ae5cb04e0b3566176113'],
            [':id' => $this->createSdk('One')->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteErr(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Sdk/deleteErrRequest.json');
    }

    /**
     * @param string $string
     *
     * @return Sdk
     * @throws Exception
     */
    private function createSdk(string $string): Sdk
    {
        $sdk = (new Sdk())
            ->setUrl($string)
            ->setHeaders([])
            ->setName($string);

        $this->dm->persist($sdk);
        $this->dm->flush();

        return $sdk;
    }

}
