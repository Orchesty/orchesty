<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Message\MessageInterface;
use Hanaboso\PipesFramework\HbPFCommonsBundle\Handler\NodeHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 *
 * @Route(service="hbpf.commons.controller.api")
 */
class ApiController extends FOSRestController
{

    /**
     * @var NodeHandler
     */
    private $nodeHandler;

    /**
     * ApiController constructor.
     *
     * @param NodeHandler $nodeHandler
     */
    public function __construct(NodeHandler $nodeHandler)
    {
        $this->nodeHandler = $nodeHandler;
    }

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
     * @return JsonResponse
     */
    public function nodeAction(string $nodeId, MessageInterface $message): JsonResponse
    {
        return new JsonResponse($this->nodeHandler->processData($nodeId, $message));
    }

}
