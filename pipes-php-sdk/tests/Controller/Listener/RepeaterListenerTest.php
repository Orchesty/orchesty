<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\Listener;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Listener\RepeaterListener;
use Hanaboso\Utils\System\PipesHeaders;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
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
     * @covers \Hanaboso\PipesPhpSdk\Listener\RepeaterListener::onRepeatableException
     *
     * @throws Exception
     */
    public function testOnRepeatableException(): void
    {
        $maxHops  = 2;
        $interval = 30;

        $node = (new Node())->setSystemConfigs((new SystemConfigDto('', '', 1, TRUE, $maxHops, $interval)));
        $this->pfd($node);

        $nodeRepository = $this->dm->getRepository(Node::class);
        $node           = $nodeRepository->findAll()[0];

        $listener = new RepeaterListener($this->dm);
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
     *
     * @throws Exception
     */
    public function testException(): void
    {
        $node = (new Node())->setSystemConfigs((new SystemConfigDto('', '', 1, FALSE, 5, 20)));
        $this->pfd($node);
        $listener = new RepeaterListener($this->dm);
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
     */
    public function testMaxHops(): void
    {
        $maxHops  = 5;
        $interval = 4;

        $listener = new RepeaterListener($this->dm);
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

}
