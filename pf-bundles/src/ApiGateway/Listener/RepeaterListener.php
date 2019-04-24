<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Listener;

use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\ApiGateway\Exceptions\OnRepeatException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ControllerExceptionListener
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Listener
 */
class RepeaterListener implements EventSubscriberInterface, LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ControllerExceptionListener constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onRepeatableException',
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @return void
     */
    public function onRepeatableException(GetResponseForExceptionEvent $event): void
    {
        $e = $event->getException();

        if (!$e instanceof OnRepeatException) {
            return;
        }

        $repeatInterval = PipesHeaders::createKey(PipesHeaders::REPEAT_INTERVAL);
        $repeatMaxHops  = PipesHeaders::createKey(PipesHeaders::REPEAT_MAX_HOPS);
        $repeatHops     = PipesHeaders::createKey(PipesHeaders::REPEAT_HOPS);
        $dto            = $e->getProcessDto();

        if (!$dto->getHeader($repeatHops) && !$dto->getHeader($repeatMaxHops) && !$dto->getHeader($repeatInterval)) {
            $dto
                ->addHeader($repeatInterval, (string) $e->getInterval())
                ->addHeader($repeatMaxHops, (string) $e->getMaxHops())
                ->addHeader($repeatHops, '0');
        }

        $currentHop = $dto->getHeader($repeatHops);
        $maxHop     = $dto->getHeader($repeatMaxHops);

        if ($currentHop >= $maxHop) {
            $ignoredHeaders = [$repeatInterval => '', $repeatMaxHops => ''];
            $headers        = array_diff_key($dto->getHeaders(), $ignoredHeaders);
        } else {
            $currentHop++;
            $e->getProcessDto()->addHeader($repeatHops, (string) $currentHop);
            $headers = $dto->getHeaders();
        }

        $this->logger->info(
            'Repeater info.',
            ['currentHop' => $currentHop, 'interval' => $e->getInterval(), 'maxHops' => $maxHop]
        );

        $response = new Response($e->getProcessDto()->getData(), 200, $headers);
        $event->setResponse($response);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}