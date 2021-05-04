<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class SdkControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 */
final class SdkControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getAllAction
     * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController
     * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::getAll
     * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler
     * @covers  \Hanaboso\PipesFramework\Configurator\Model\SdkManager::getAll
     * @covers  \Hanaboso\PipesFramework\Configurator\Model\SdkManager
     *
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createSdk('One');
        $this->createSdk('Two');

        $this->assertResponse(__DIR__ . '/data/Sdk/getAllRequest.json', ['id' => '5e32a7a41ffeab2445696983']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getOneAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::getOne
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::getOne
     *
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/Sdk/getOneRequest.json',
            ['id' => '5e32a9b8a1b2a70fef6fa273'],
            [':id' => $this->createSdk('One')->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::getOneAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::getOne
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::getOne
     *
     * @throws Exception
     */
    public function testGetOneNotFound(): void
    {
        $this->assertResponse(__DIR__ . '/data/Sdk/getOneNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::createAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::create
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::create
     *
     * @throws Exception
     */
    public function testCreate(): void
    {
        $this->assertResponse(__DIR__ . '/data/Sdk/createRequest.json', ['id' => '5e32aab74c2bd32924205303']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::createAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::create
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::create
     *
     * @throws Exception
     */
    public function testCreateErr(): void
    {
        $this->assertResponse(__DIR__ . '/data/Sdk/createErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::updateAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::update
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::get
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::update
     *
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/Sdk/updateRequest.json',
            ['id' => '5e32ac41505d6e1b5047eb43'],
            [':id' => $this->createSdk('One')->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::updateAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::update
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::get
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::update
     *
     * @throws Exception
     */
    public function testUpdateNotFound(): void
    {
        $this->assertResponse(__DIR__ . '/data/Sdk/updateNotFoundRequest.json',);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::deleteAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::delete
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::get
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::delete
     *
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/Sdk/deleteRequest.json',
            ['id' => '5e32ae5cb04e0b3566176113'],
            [':id' => $this->createSdk('One')->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::deleteAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::delete
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::delete
     *
     * @throws Exception
     */
    public function testDeleteErr(): void
    {
        $this->assertResponse(__DIR__ . '/data/Sdk/deleteErrRequest.json',);
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
            ->setKey($string)
            ->setValue($string);

        $this->dm->persist($sdk);
        $this->dm->flush();

        return $sdk;
    }

}
