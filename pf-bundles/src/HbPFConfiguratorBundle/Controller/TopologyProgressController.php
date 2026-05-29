<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyProgressHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class TopologyProgressController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class TopologyProgressController
{

    use ControllerTrait;

    /**
     * TopologyProgressController constructor.
     *
     * @param TopologyProgressHandler $handler
     */
    public function __construct(private readonly TopologyProgressHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     * @param string  $topologyId
     *
     * @return Response
     * @throws GridException
     * @throws MongoDBException
     */
    #[Route('/progress/topology/{topologyId}', methods: ['GET'])]
    public function getProgressTopologyAction(Request $request, string $topologyId): Response
    {
        $query = Json::decode($request->query->get('filter', '{}'));
        $dto   = new GridRequestDto($query);

            $dto->setAdditionalFilters(
                [
                    [
                        [
                            GridFilterAbstract::COLUMN   => 'topologyId',
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                            GridFilterAbstract::VALUE    => [$topologyId],
                        ],
                    ],
                ],
            );

        $data = $this->handler->getProgress($dto);

        return $this->getResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws MongoDBException
     */
    #[Route('/progress}', methods: ['GET'])]
    public function getProgressesAction(Request $request): Response
    {
        $query = Json::decode($request->query->get('filter', '{}'));
        $dto   = new GridRequestDto($query);

        $data = $this->handler->getProgress($dto);

        return $this->getResponse($data);
    }

}
