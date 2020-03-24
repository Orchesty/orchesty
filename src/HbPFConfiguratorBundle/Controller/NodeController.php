<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class NodeController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
class NodeController
{

    use ControllerTrait;

    /**
     * @var NodeHandler
     */
    private NodeHandler $nodeHandler;

    /**
     * NodeController constructor.
     *
     * @param NodeHandler $nodeHandler
     */
    public function __construct(NodeHandler $nodeHandler)
    {
        $this->nodeHandler = $nodeHandler;
        $this->logger      = new NullLogger();
    }

    /**
     * @Route("/topologies/{id}/nodes", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function getNodesAction(string $id): Response
    {
        return $this->getResponse($this->nodeHandler->getNodes($id));
    }

    /**
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"}, methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
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
     * @Route("/nodes/{id}", defaults={}, requirements={"id": "\w+"}, methods={"PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateNodeAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->nodeHandler->updateNode($id, $request->request->all()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
