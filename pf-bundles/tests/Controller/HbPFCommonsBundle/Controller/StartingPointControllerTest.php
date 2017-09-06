<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 12:10 PM
 */

namespace Tests\Controller\HbPFCommonsBundle\Controller;

use Hanaboso\PipesFramework\HbPFCommonsBundle\Handler\StartingPointHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class StartingPointControllerTest
 *
 * @package Tests\Controller\HbPFCommonsBundle\Controller
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

        $this->client->getContainer()->set('hbpf.commons.handler.starting_point', $startingPointHandler);

        $this->client->request(
            'POST',
            '/api/run/1/mapper_123',
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