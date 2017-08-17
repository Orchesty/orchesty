<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Message\MessageInterface;
use Hanaboso\PipesFramework\Commons\Node\NodeInterface;
use Hanaboso\PipesFramework\Commons\Node\NodeRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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

}
