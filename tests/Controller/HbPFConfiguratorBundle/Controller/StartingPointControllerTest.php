<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:10 PM
 */

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class StartingPointControllerTest
 *
 * @package Tests\Controller\HbPFConfiguratorBundle\Controller
 */
class StartingPointControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers ApiController::runAction
     */
    public function testRunWithRequest(): void
    {
        $startingPointHandler = $this->createMock(StartingPointHandler::class);
        $startingPointHandler->method('runWithRequest')->willReturn(NULL);

        $this->client->getContainer()->set('hbpf.handler.starting_point', $startingPointHandler);

        $this->client->request(
            'POST',
            '/api/topologies/1/nodes/mapper_123/run',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"test":1}'
        );

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers ApiController::runTest
     */
    public function testRunTest(): void
    {
        $data = [
            'status'  => TRUE,
            'message' => '5/5 Nodes OK.',
            'failed'  => [],
        ];

        $startingPointHandler = $this->createMock(StartingPointHandler::class);
        $startingPointHandler->method('runTest')->willReturn($data);

        $this->client->getContainer()->set('hbpf.handler.starting_point', $startingPointHandler);

        $this->client->request(
            'GET',
            '/api/topologies/aa/test',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($data, json_decode($response->getContent(), TRUE));
    }

}