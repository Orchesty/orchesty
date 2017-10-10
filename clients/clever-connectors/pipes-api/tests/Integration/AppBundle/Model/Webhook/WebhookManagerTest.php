<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Webhook;

use CleverConnectors\AppBundle\Document\Webhook;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class WebhookManagerTest
 *
 * @package Tests\Integration\AppBundle\Model
 */
class WebhookManagerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dm->getConnection()->dropDatabase('clever-connectors');
    }

    /**
     *
     */
    public function testSubscribe(): void
    {
        $system  = $this->container->get('systems.null.user.group');
        $webhook = $this->container->get('manager.webhook');
        $this->setProperty($webhook, 'curl', $this->mockCurl());

        $this->container->get('systems.manager')->installSystem('someUser', 'null.user.group', 'token');

        // Subscribe
        $webhook->subscribe($system, 'someUser', 'token');
        /** @var Webhook $res */
        $res = $this->dm->getRepository(Webhook::class)->findAll();
        self::assertEquals(1, count($res));
        $res = $res[0];
        self::assertEquals('someUser', $res->getUser());
        self::assertEquals('top', $res->getTopologyName());
        self::assertEquals('node', $res->getNodeName());
        self::assertEquals('null.user.group', $res->getSystemKey());

        // Update
        $webhook->update($system, 'someUser', 'token');
        $res = $this->dm->getRepository(Webhook::class)->findAll();
        self::assertEquals(1, count($res));

        // Unsubscribe
        $webhook->unsubscribe($system, 'someUser');
        $res = $this->dm->getRepository(Webhook::class)->findAll();
        self::assertEquals(0, count($res));
    }

    /**
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $res  = new ResponseDto(200, '', 'body', []);
        $curl->method('send')->willReturn($res);

        return $curl;
    }

}