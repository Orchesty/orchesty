<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller;

use Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Handler\McpHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class McpController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller
 */
final class McpController extends AbstractController
{

    use ControllerTrait;

    /**
     * McpController constructor.
     *
     * @param McpHandler $handler
     */
    public function __construct(private McpHandler $handler)
    {
    }

    /**
     * @return Response
     */
    #[Route('/mcp/topologies/entities/manifest.json', methods: ['GET'])]
    public function getTopologiesEntitiesManifestAction(): Response
    {
        return $this->getResponse($this->handler->getTopologiesEntitiesManifest());
    }

    /**
     * @return Response
     */
    #[Route('/mcp/manifest.json', methods: ['GET'])]
    public function getManifestAction(): Response
    {
        return $this->getResponse($this->handler->getManifest());
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/mcp/run', methods: ['POST'])]
    public function runAction(Request $request): Response
    {
        return $this->getResponse($this->handler->run($request->request->all()));
    }

}
