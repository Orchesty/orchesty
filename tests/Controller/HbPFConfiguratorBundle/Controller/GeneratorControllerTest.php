<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.9.17
 * Time: 13:02
 */

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class GeneratorControllerTest
 *
 * @coversDefaultClass Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\GeneratorController
 * @package            Tests\Controller\HbPFConfiguratorBundle\Controller
 */
final class GeneratorControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers ::generateAction()
     */
    public function testGenerateActionTrue(): void
    {
        $handler = $this->createMock(GeneratorHandler::class);
        $handler->method('generateTopology')->willReturn(TRUE);

        $this->client->getContainer()->set('hbpf.handler.generator_handler', $handler);

        $this->client->request(
            'GET',
            '/api/topology/generate/123ABC',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $response = $this->client->getResponse();

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @covers ::generateAction()
     */
    public function testGenerateActionFalse(): void
    {
        $handler = $this->createMock(GeneratorHandler::class);
        $handler->method('generateTopology')->willReturn(FALSE);

        $this->client->getContainer()->set('hbpf.handler.generator_handler', $handler);

        $this->client->request(
            'GET',
            '/api/topology/generate/123ABC',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $response = $this->client->getResponse();

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @covers ::runAction()
     */
    public function testRunAction(): void
    {
        $handler = $this->createMock(GeneratorHandler::class);
        $handler->method('runTopology')->willReturn([1]);

        $this->client->getContainer()->set('hbpf.handler.generator_handler', $handler);

        $this->client->request(
            'GET',
            '/api/topology/run/123ABC',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $response = $this->client->getResponse();

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @covers ::stopAction()
     */
    public function testStopTopology(): void
    {
        $handler = $this->createMock(GeneratorHandler::class);
        $handler->method('stopTopology')->willReturn([]);

        $this->client->getContainer()->set('hbpf.handler.generator_handler', $handler);

        $this->client->request(
            'GET',
            '/api/topology/stop/123ABC',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $response = $this->client->getResponse();

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @covers ::deleteAction()
     */
    public function testDeleteAction(): void
    {
        $handler = $this->createMock(GeneratorHandler::class);
        $handler->method('stopTopology')->willReturn([]);
        $handler->method('destroyTopology')->willReturn(TRUE);

        $this->client->getContainer()->set('hbpf.handler.generator_handler', $handler);

        $this->client->request(
            'GET',
            '/api/topology/delete/123ABC',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $response = $this->client->getResponse();

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @covers ::infoAction()
     */
    public function testInfoAction(): void
    {
        $handler = $this->createMock(GeneratorHandler::class);
        $handler->method('infoTopology')->willReturn([1]);

        $this->client->getContainer()->set('hbpf.handler.generator_handler', $handler);

        $this->client->request(
            'GET',
            '/api/topology/info/123ABC',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $response = $this->client->getResponse();

        $this->assertEquals($response->getStatusCode(), 200);
    }

}
