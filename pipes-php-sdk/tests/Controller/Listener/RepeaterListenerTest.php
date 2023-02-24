<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\Listener;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Listener\RepeaterListener;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Node;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository\NodeRepository;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Class RepeaterListenerTest
 *
 * @package PipesPhpSdkTests\Controller\Listener
 *
 * @covers  \Hanaboso\PipesPhpSdk\Listener\RepeaterListener
 */
final class RepeaterListenerTest extends ControllerTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Listener\RepeaterListener::onRepeatableException
     * @covers \Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Node
     * @covers \Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Node::fromArray
     * @covers \Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository\NodeRepository
     * @covers \Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository\NodeFilter
     *
     * @return void
     * @throws GuzzleException
     * @throws PipesFrameworkException
     * @throws Exception
     */
    public function testOnRepeatableException(): void
    {
        $this->privateSetUp();
        $this->mockServer->addMock(
            new Mock(
                '/document/Node?filter={"ids":[""],"deleted":null}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(
                    200,
                    [PipesHeaders::REPEAT_MAX_HOPS => '3', PipesHeaders::REPEAT_INTERVAL => '60_000'],
                    Json::encode([new Node(['system_configs' => new SystemConfigDto()])]),
                ),
            ),
        );

        $maxHops  = 3;
        $interval = 60_000;

        $node = (new Node())->setSystemConfigs((new SystemConfigDto('', '', 1, TRUE, $maxHops, $interval)));

        /** @var NodeRepository $nodeRepository */
        $nodeRepository = self::getContainer()->get('hbpf.node.repository');

        $listener = new RepeaterListener($nodeRepository);
        $dto      = new ProcessDto();
        $dto->setHeaders([PipesHeaders::NODE_ID => $node->getId()]);

        $eventMock = $this->mockEvent(new OnRepeatException($dto));
        $listener->onRepeatableException($eventMock);
        /** @var Response $response */
        $response = $eventMock->getResponse();
        self::assertEquals($maxHops, $response->headers->get(PipesHeaders::REPEAT_MAX_HOPS));
        self::assertEquals($interval, $response->headers->get(PipesHeaders::REPEAT_INTERVAL));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Listener\RepeaterListener::onRepeatableException
     *
     * @throws Exception
     */
    public function testOnRepeatableExceptionReturn(): void
    {
        $listener  = self::getContainer()->get('listener.repeater');
        $eventMock = $this->mockEvent(new Exception('Upps, somehing went wrong.'));
        $listener->onRepeatableException($eventMock);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Listener\RepeaterListener::onRepeatableException
     * @covers \Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Node
     *
     * @throws Exception
     */
    public function testException(): void
    {
        $this->privateSetUp();
        $this->mockServer->addMock(
            new Mock(
                '/document/Node?filter={"ids":[""],"deleted":null}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(
                    200,
                    [PipesHeaders::REPEAT_MAX_HOPS => '5', PipesHeaders::REPEAT_INTERVAL => '20'],
                    Json::encode([new Node(['system_configs' => new SystemConfigDto()])]),
                ),
            ),
        );
        $node = (new Node())->setSystemConfigs((new SystemConfigDto('', '', 1, FALSE, 5, 20)));

        /** @var NodeRepository $nodeRepository */
        $nodeRepository = self::getContainer()->get('hbpf.node.repository');

        $listener = new RepeaterListener($nodeRepository);
        $dto      = new ProcessDto();
        $dto->setHeaders([PipesHeaders::NODE_ID => $node->getId()]);

        $eventMock = $this->mockEvent(new OnRepeatException($dto));
        $listener->onRepeatableException($eventMock);
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Listener\RepeaterListener::onRepeatableException
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testMaxHops(): void
    {
        $this->privateSetUp();
        $this->mockServer->addMock(
            new Mock(
                '/document/Node?filter={"ids":[""],"deleted":null}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(
                    200,
                    [PipesHeaders::REPEAT_MAX_HOPS => '5', PipesHeaders::REPEAT_INTERVAL => '4'],
                    Json::encode([new Node(['system_configs' => new SystemConfigDto()])]),
                ),
            ),
        );

        $maxHops  = 5;
        $interval = 4;

        /** @var NodeRepository $nodeRepository */
        $nodeRepository = self::getContainer()->get('hbpf.node.repository');

        $listener = new RepeaterListener($nodeRepository);
        $dto      = new ProcessDto();

        $dto->addHeader(PipesHeaders::REPEAT_HOPS, '5');

        $exception = new OnRepeatException($dto);
        $exception->setInterval($maxHops);
        $exception->setMaxHops($interval);

        $eventMock = $this->mockEvent($exception);
        $listener->onRepeatableException($eventMock);
        /** @var Response $response */
        $response = $eventMock->getResponse();

        self::assertEquals(
            $maxHops,
            (int) $response->headers->get(PipesHeaders::REPEAT_INTERVAL),
        );
        self::assertEquals(
            $interval,
            (int) $response->headers->get(PipesHeaders::REPEAT_MAX_HOPS),
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Listener\RepeaterListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [KernelEvents::EXCEPTION => ['onRepeatableException', 2_048]],
            RepeaterListener::getSubscribedEvents(),
        );
    }

    /**
     * @param Throwable $exception
     *
     * @return ExceptionEvent
     */
    private function mockEvent(Throwable $exception): ExceptionEvent
    {
        $eventMock = self::createPartialMock(ExceptionEvent::class, ['getThrowable']);
        $eventMock->method('getThrowable')->willReturn($exception);

        return $eventMock;
    }

    /**
     * @return void
     */
    private function privateSetUp(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
    }

}
