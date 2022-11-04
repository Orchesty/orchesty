<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class ApiTokenControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 */
final class ApiTokenControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetApiTokensActions(): void
    {
        $this->createApiToken('One');
        $this->createApiToken('Two');

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApiToken/getAllRequest.json',
            ['id' => '5e32a9b8a1b2a70fef6fa273'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $this->createApiToken('One');
        $this->createApiToken('Two');

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApiToken/getAllFilterRequest.json',
            ['id' => '5e32a9b8a1b2a70fef6fa273'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOneNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/ApiToken/getAllNotFoundRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApiToken/createRequest.json',
            [
                'id'  => '5e32aab74c2bd32924205303',
                'key' => '0a21a5c5253aa04eff802f4454810f5357d5f70c68af87233b9a45506cf84273',
            ],
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
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\SdkController::deleteAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::delete
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\SdkHandler::get
     * @covers \Hanaboso\PipesFramework\Configurator\Model\SdkManager::delete
     *
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/ApiToken/deleteRequest.json',
            ['id' => '5e32ae5cb04e0b3566176113'],
            [':id' => $this->createApiToken('1')->getId()],
        );
    }

    /**
     * @param string $string
     *
     * @return ApiToken
     * @throws Exception
     */
    private function createApiToken(string $string): ApiToken
    {
        $apiToken = (new ApiToken())
            ->setKey($string)
            ->setScopes([$string])
            ->setExpireAt('2022-11-03 05:43:31.000Z')
            ->setUser(ApplicationController::SYSTEM_USER);

        $this->dm->persist($apiToken);
        $this->dm->flush();

        return $apiToken;
    }

}
