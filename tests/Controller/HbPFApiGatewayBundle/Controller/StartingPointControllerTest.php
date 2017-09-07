<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:10 PM
 */

namespace Tests\Controller\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\StartingPointHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class StartingPointControllerTest
 *
 * @package Tests\Controller\HbPFApiGatewayBundle\Controller
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
            '/api/gateway/topologies/1/nodes/mapper_123/run',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"test":1}'
        );

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

}