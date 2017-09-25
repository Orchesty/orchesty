<?php declare(strict_types=1);

namespace Tests\Controller\ApiGateway\Listener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ControllerExceptionListenerTest
 *
 * @package Tests\Controller\ApiGateway\Listener
 */
class ControllerExceptionListenerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testListener(): void
    {
        $this->client->request('GET', '/nodes/oiz5', [], [], []);

        /** @var JsonResponse $response */
        $response = $this->client->getResponse();

        self::assertEquals(400, $response->getStatusCode());
    }

}