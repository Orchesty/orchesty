<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Listener;

use Exception;
use Hanaboso\PipesFramework\Commons\Enum\MetricsEnum;
use Hanaboso\PipesFramework\Commons\Metrics\Exception\SystemMetricException;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Metrics\SystemMetrics;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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

    public const KEY_TIMESTAMP = 'timestamp';
    public const KEY_CPU       = 'cpu';

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
            KernelEvents::REQUEST   => 'onKernelRequest',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    /**
     * Adds system metrics values to request object
     *
     * @param GetResponseEvent $event
     *
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        try {
            if (!$this->isPipesRequest($event->getRequest())) {
                return;
            }

            $metricsData = $this->getCurrentMetrics();
            $event->getRequest()->attributes->add([self::METRICS_ATTRIBUTES_KEY => $metricsData]);
        } catch (Exception $e) {
            $this->logger->error('Metrics listener onKernelRequest exception', ['exception' => $e]);
        }
    }

    /**
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event): void
    {
        try {
            $request = $event->getRequest();

            if (!$this->isPipesRequest($request)) {
                return;
            }

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

        $startTime      = $startMetrics[self::KEY_TIMESTAMP];
        $startCpuUser   = $startMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_USER];
        $startCpuKernel = $startMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_KERNEL];

        $endMetrics = $this->getCurrentMetrics();

        $requestDuration = $endMetrics[self::KEY_TIMESTAMP] - $startTime;
        $cpuUserTime     = $endMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_USER] - $startCpuUser;
        $cpuKernelTime   = $endMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_KERNEL] - $startCpuKernel;

        $this->sender->send(
            [
                MetricsEnum::REQUEST_TOTAL_DURATION => $requestDuration,
                MetricsEnum::CPU_USER_TIME          => $cpuUserTime,
                MetricsEnum::CPU_KERNEL_TIME        => $cpuKernelTime,
            ],
            [
                MetricsEnum::HOST => gethostname(),
                MetricsEnum::URI => $request->getRequestUri(),
                MetricsEnum::TOPOLOGY_ID => $request->headers->get(
                    PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID)
                ),
                MetricsEnum::CORRELATION_ID => $request->headers->get(
                    PipesHeaders::createKey(PipesHeaders::CORRELATION_ID)
                ),
            ]
        );
    }

    /**
     * @return array
     */
    private function getCurrentMetrics(): array
    {
        return [
            self::KEY_TIMESTAMP => SystemMetrics::getCurrentTimestamp(),
            self::KEY_CPU       => SystemMetrics::getCpuTimes(),
        ];
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function isPipesRequest(Request $request): bool
    {
        $topologyIdHeader = PipesHeaders::createKey(PipesHeaders::TOPOLOGY_ID);
        $correlationIdHeader = PipesHeaders::createKey(PipesHeaders::CORRELATION_ID);

        if ($request->headers->has($topologyIdHeader) &&
            $request->headers->has($correlationIdHeader)
        ) {
            return TRUE;
        }

        return FALSE;
    }

}
