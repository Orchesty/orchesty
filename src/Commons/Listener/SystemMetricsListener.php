<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Listener;

use Exception;
use Hanaboso\PipesFramework\Commons\Enum\MetricsEnum;
use Hanaboso\PipesFramework\Commons\Metrics\Exception\SystemMetricException;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Utils\CurlMetricUtils;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SystemMetricsListener
 *
 * @package Hanaboso\PipesFramework\Commons\Listener
 */
class SystemMetricsListener implements EventSubscriberInterface, LoggerAwareInterface
{

    public const METRICS_ATTRIBUTES_KEY = 'system_metrics';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InfluxDbSender
     */
    private $sender;

    /**
     * ControllerExceptionListener constructor.
     *
     * @param InfluxDbSender $sender
     */
    public function __construct(InfluxDbSender $sender)
    {
        $this->sender = $sender;
        $this->logger = new NullLogger();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    /**
     * Adds system metrics values to request object
     *
     * @param FilterControllerEvent $event
     *
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        try {
            if (!$event->isMasterRequest() || !$this->isPipesRequest($event->getRequest())) {
                return;
            }

            $metricsData = CurlMetricUtils::getCurrentMetrics();
            $event->getRequest()->attributes->add([self::METRICS_ATTRIBUTES_KEY => $metricsData]);
            $this->logger->info('onKernelController', ['RequestC' => json_encode($event->getRequest()->attributes->all())]);
            $this->logger->info('onKernelController', ['RequestC' => json_encode($event->getRequest()->headers->all())]);
        } catch (Exception $e) {
            $this->logger->error('Metrics listener onKernelController exception', ['exception' => $e]);
        }
    }

    /**
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event): void
    {
        try {
            if (!$event->isMasterRequest() || !$this->isPipesRequest($event->getRequest())) {
                return;
            }

            $this->logger->info('onKernelTerminate', ['RequestT' => json_encode($event->getRequest()->attributes->all())]);
            $this->logger->info('onKernelTerminate', ['RequestT' => json_encode($event->getRequest()->headers->all())]);

            $request = $event->getRequest();
            if (!$request->attributes->has(self::METRICS_ATTRIBUTES_KEY)) {
                throw new SystemMetricException('Initial system metrics not found.');
            }

            $this->sendMetrics($request);
        } catch (Exception $e) {
            $this->logger->error('Metrics listener onKernelTerminate exception', ['exception' => $e]);
        }
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

    /**
     * @param Request $request
     */
    private function sendMetrics(Request $request): void
    {
        $startMetrics = $request->attributes->get(self::METRICS_ATTRIBUTES_KEY);
        $times        = CurlMetricUtils::getTimes($startMetrics);

        $this->sender->send(
            [
                MetricsEnum::REQUEST_TOTAL_DURATION => $times[CurlMetricUtils::KEY_REQUEST_DURATION],
                MetricsEnum::CPU_USER_TIME          => $times[CurlMetricUtils::KEY_USER_TIME],
                MetricsEnum::CPU_KERNEL_TIME        => $times[CurlMetricUtils::KEY_KERNEL_TIME],
            ],
            [
                MetricsEnum::HOST           => gethostname(),
                MetricsEnum::URI            => $request->getRequestUri(),
                MetricsEnum::TOPOLOGY_ID    => $request->headers->get(
                    PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)
                ),
                MetricsEnum::CORRELATION_ID => $request->headers->get(
                    PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)
                ),
                MetricsEnum::NODE_ID        => $request->headers->get(
                    PipesHeaders::createKey(PipesHeaders::NODE_ID)
                ),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function isPipesRequest(Request $request): bool
    {
        $topologyIdHeader    = PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID);
        $correlationIdHeader = PipesHeaders::createKey(PipesHeaders::CORRELATION_ID);
        $nodeIdHeader        = PipesHeaders::createKey(PipesHeaders::NODE_ID);

        if (
            $request->headers->has($topologyIdHeader)
            && $request->headers->has($correlationIdHeader)
            && $request->headers->has($nodeIdHeader)
        ) {
            return TRUE;
        }

        return FALSE;
    }

}
