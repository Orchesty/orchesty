<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\ApiGateway\Listener;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ControllerExceptionListener
 *
 * @package Hanaboso\PipesFramework\ApiGateway\Listener
 */
class ControllerExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{

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
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $e = $event->getException();

        if (!$e instanceof PipesFrameworkException) {
            return;
        }

        $this->logger->error('Controller exception.', ['exception' => $e]);
        $event->setResponse(new JsonResponse(ControllerUtils::createExceptionData($e), 400));
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