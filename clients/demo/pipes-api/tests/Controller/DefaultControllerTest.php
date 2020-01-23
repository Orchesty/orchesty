<?php declare(strict_types=1);

namespace Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultControllerTest
 *
 * @package Tests\Controller
 */
final class DefaultControllerTest extends WebTestCase
{

    /**
     *
     */
    public function testIndex(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        /** @var Response $response */
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
        self::assertNotEmpty($response->getContent());
    }

}
