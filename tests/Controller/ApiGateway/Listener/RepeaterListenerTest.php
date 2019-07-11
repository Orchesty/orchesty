<?php declare(strict_types=1);

namespace Tests\Controller\ApiGateway\Listener;

use Exception;
use Hanaboso\CommonsBundle\Document\Node;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Model\Dto\SystemConfigDto;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\ApiGateway\Listener\RepeaterListener;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Tests\ControllerTestCaseAbstract;
use Throwable;

/**
 * Class RepeaterListenerTest
 *
 * @package Tests\Controller\ApiGateway\Listener
 */
final class RepeaterListenerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testOnRepeatableException(): void
    {
        $node = (new Node())->setSystemConfigs((new SystemConfigDto('', '', 1, TRUE, 5, 20)));
        $this->persistAndFlush($node);

        $nodeRepository = $this->dm->getRepository(Node::class);
        $node           = $nodeRepository->findAll()[0];

        $listener = new RepeaterListener($this->dm);
        $dto      = new ProcessDto();
        $dto->setHeaders([PipesHeaders::createKey(PipesHeaders::NODE_ID) => $node->getId()]);

        $eventMock = $this->mockEvent(new OnRepeatException($dto));

        for ($i = 1; $i <= 5; $i++) {
            $listener->onRepeatableException($eventMock);
            /** @var Response $response */
            $response         = $eventMock->getResponse();
            $currentHop       = $response->headers->get(PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS));
            $maxHop           = $response->headers->get(PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS));
            $repeaterInterval = $response->headers->get(PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL));

            self::assertEquals($i, $currentHop);
            self::assertEquals(5, $maxHop);
            self::assertEquals(20, $repeaterInterval);
        }

    }

    /**
     * @throws Exception
     */
    function testException(): void
    {
        $node = (new Node())->setSystemConfigs((new SystemConfigDto('', '', 1, FALSE, 5, 20)));
        $this->persistAndFlush($node);
        $listener = new RepeaterListener($this->dm);
        $dto      = new ProcessDto();
        $dto->setHeaders([PipesHeaders::createKey(PipesHeaders::NODE_ID) => $node->getId()]);

        $eventMock = $this->mockEvent(new OnRepeatException($dto));
        $listener->onRepeatableException($eventMock);
    }

    /**
     * @throws Exception
     */
    function testMaxHops(): void
    {
        $listener = new RepeaterListener($this->dm);
        $dto      = new ProcessDto();

        $dto->addHeader(PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS), '5');

        $exception = new OnRepeatException($dto);
        $exception->setInterval(5);
        $exception->setMaxHops(4);

        $eventMock = $this->mockEvent($exception);
        $listener->onRepeatableException($eventMock);
        /** @var Response $response */
        $response = $eventMock->getResponse();

        self::assertEquals(0, (int) $response->headers->get(
            PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL)
        ));

        self::assertArrayNotHasKey(
            PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS),
            $response->headers->all());

        self::assertArrayHasKey(
            PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS),
            $response->headers->all());

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @param Throwable $exception
     *
     * @return GetResponseForExceptionEvent | MockObject
     * @throws Exception
     */
    private function mockEvent(Throwable $exception)
    {
        /** @var GetResponseForExceptionEvent | MockObject $eventMock */
        $eventMock = self::createPartialMock(
            GetResponseForExceptionEvent::class,
            ['getException']
        );

        $eventMock
            ->method('getException')
            ->will($this->returnValue($exception));

        return $eventMock;
    }

}
