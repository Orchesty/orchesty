<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\NodeHandler;
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
class NodeController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var NodeHandler
     */
    private $nodeHandler;

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
        $data = $this->nodeHandler->getNodes($id);

        return $this->getResponse($data);
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
            $data = $this->nodeHandler->getNode($id);

            return $this->getResponse($data);
        } catch (NodeException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/nodes/{id}/{request}", defaults={}, requirements={"id": "\w+"}, methods={"PATCH", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function updateNodeAction(Request $request, string $id): Response
    {
        try {
            $data = $this->nodeHandler->updateNode($id, $request->request->all());

            return $this->getResponse($data);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
