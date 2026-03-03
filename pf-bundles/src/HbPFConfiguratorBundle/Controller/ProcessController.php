<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\ProcessHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class ProcessController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class ProcessController
{

    use ControllerTrait;

    /**
     * ProcessController constructor.
     *
     * @param ProcessHandler $handler
     */
    public function __construct(private readonly ProcessHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/processes', methods: [Request::METHOD_GET])]
    public function getProcessesAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getProcesses(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/processes/total', methods: [Request::METHOD_GET])]
    public function getProcessesTotalAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getProcessesTotal(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/processes/graph', methods: [Request::METHOD_GET])]
    public function getProcessesGraphAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getProcessesGraph(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
                $request->query->getInt(AggregationFilterUtils::BUCKETS, AggregationFilterUtils::DEFAULT_BUCKETS),
            ),
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/processes/topologies', methods: [Request::METHOD_GET])]
    public function getProcessesTopologies(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getProcessesTopologies(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

}
