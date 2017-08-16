<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRouteableInterface;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRouteManager;
use Hanaboso\PipesFramework\Commons\Message\MessageInterface;
use Hanaboso\PipesFramework\Commons\Node\NodeInterface;
use Hanaboso\PipesFramework\Commons\Node\NodeRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 */
class ApiController extends FOSRestController
{

    /**
     * @Route("/api/nodes/{nodeId}/process", defaults={}, requirements={"nodeId": "\w+"})
     * @ParamConverter(
     *     "message",
     *     class="Hanaboso\PipesFramework\Commons\Message\DefaultMessage",
     *     converter="fos_rest.request_body"
     * )
     *
     * @param string           $nodeId
     * @param MessageInterface $message
     *
     * @return Response
     */
    public function nodeAction(string $nodeId, MessageInterface $message): Response
    {
        /** @var NodeRepository $repo */
        $repo = $this->container->get('hbpf.node_repository');

        /** @var NodeInterface $node */
        $node = $repo->get($nodeId);

        return $this->handleView($this->view($node->processData($nodeId, $message)));
    }

    /**
     * @Route("/api/nodes/{nodeId}/custom_routes", defaults={}, requirements={"nodeId": "\w+"})
     *
     * @param string $nodeId
     *
     * @return Response
     */
    public function getCustomRoutesForNode(string $nodeId): Response
    {
        /** @var NodeRepository $repo */
        $repo = $this->container->get('hbpf.node_repository');

        /** @var CustomRouteableInterface $node */
        $node = $repo->get($nodeId);

        $result = [];
        if ($node instanceof CustomRouteableInterface) {
            $result = $node->getRoutes();
        }

        return $this->handleView($this->view($result));
    }

    /**
     * @Route("/api/nodes/{nodeId}/custom_routes/{partUrl}", defaults={}, requirements={"nodeId":"\w+"}, requirements={"partUrl":".+"})
     *
     * @param Request $request
     * @param string  $nodeId
     * @param string  $partUrl
     *
     * @return Response
     */
    public function nodeCustomRouteAction(Request $request, string $nodeId, string $partUrl): Response
    {
        /** @var NodeRepository $repo */
        $repo = $this->container->get('hbpf.node_repository');

        /** @var CustomRouteableInterface $node */
        $node = $repo->get($nodeId);

        if ($node instanceof CustomRouteableInterface) {
            /** @var CustomRouteManager $customRouteManager */
            $customRouteManager = $this->container->get('hbpf.custom_route_manager');

            return $this->handleView(
                $this->view($customRouteManager->processRoute($node, $request, $partUrl))
            );
        } else {
            throw $this->createNotFoundException();
        }

    }

}
