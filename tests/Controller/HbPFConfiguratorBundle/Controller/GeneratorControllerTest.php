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
 * @package Tests\Controller\HbPFConfiguratorBundle\Controller
 */
class GeneratorControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers       GeneratorController::generateAction()
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
     * @covers       GeneratorController::generateAction()
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

}
