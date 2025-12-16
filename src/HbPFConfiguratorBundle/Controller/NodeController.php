<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class NodeController
{

    use ControllerTrait;

    /**
     * NodeController constructor.
     *
     * @param NodeHandler $nodeHandler
     */
    public function __construct(private NodeHandler $nodeHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/topologies/nodes', methods: ['GET'])]
    public function getTopologiesNodesAction(): Response
    {
        try {
            return $this->getResponse($this->nodeHandler->getTopologiesWithNodes());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/topologies/{id}/nodes', requirements: ['id' => '\w+'], methods: ['GET'])]
    public function getNodesAction(string $id): Response
    {
        return $this->getResponse($this->nodeHandler->getNodes($id));
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/nodes/{id}', requirements: ['id' => '\w+'], methods: ['GET'])]
    public function getNodeAction(string $id): Response
    {
        try {
            return $this->getResponse($this->nodeHandler->getNode($id));
        } catch (NodeException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/nodes/{id}', requirements: ['id' => '\w+'], methods: ['PATCH'])]
    public function updateNodeAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->nodeHandler->updateNode($id, $request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
