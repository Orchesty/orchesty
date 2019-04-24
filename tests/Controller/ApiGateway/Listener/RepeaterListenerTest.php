<?php declare(strict_types=1);

namespace Tests\Controller\ApiGateway\Listener;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\ApiGateway\Exceptions\OnRepeatException;
use Hanaboso\PipesFramework\ApiGateway\Listener\RepeaterListener;
use PHPUnit\Framework\MockObject\MockObject;
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
        $listener = new RepeaterListener();
        $dto      = new ProcessDto();

        $eventMock = $this->mockEvent(new OnRepeatException($dto));

        for ($i = 1; ; $i++) {
            $listener->onRepeatableException($eventMock);
            $currentHop = $eventMock->getResponse()->headers->get(PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS));
            $maxHop     = $eventMock->getResponse()->headers->get(PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS));

            if ($currentHop > $maxHop) {
                self::assertArrayNotHasKey(
                    PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS),
                    $eventMock->getResponse()->headers->all()
                );
                break;
            }
            self::assertEquals($i, $currentHop);

        }
    }

    /**
     * @throws Exception
     */
    function testMaxHops(): void
    {
        $listener = new RepeaterListener();
        $dto      = new ProcessDto();

        $dto->addHeader(PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS), '5');

        $exception = new OnRepeatException($dto);
        $exception->setInterval(5);
        $exception->setMaxHops(4);

        $eventMock = $this->mockEvent($exception);
        $listener->onRepeatableException($eventMock);

        self::assertEquals(0, (int) $eventMock->getResponse()->headers->get(
            PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL)
        ));

        self::assertArrayNotHasKey(
            PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS),
            $eventMock->getResponse()->headers->all());

        self::assertArrayHasKey(
            PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS),
            $eventMock->getResponse()->headers->all());

        self::assertEquals(200, $eventMock->getResponse()->getStatusCode());
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