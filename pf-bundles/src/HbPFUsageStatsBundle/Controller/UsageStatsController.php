<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Controller;

use Hanaboso\PipesFramework\HbPFUsageStatsBundle\Handler\UsageStatsHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class UsageStatsController
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Controller
 */
final class UsageStatsController
{

    use ControllerTrait;

    /**
     * UsageStatsController constructor.
     *
     * @param UsageStatsHandler $usageStatsHandler
     */
    public function __construct(private readonly UsageStatsHandler $usageStatsHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @Route("/usage-stats/emit-event", methods={"POST", "OPTIONS"})
     * @param Request $request
     *
     * @return Response
     */
    public function emitEventAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->usageStatsHandler->emitEvent($request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
