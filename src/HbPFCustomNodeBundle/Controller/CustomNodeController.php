<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:53 PM
 */

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CustomNodeController
 *
 * @package Hanaboso\PipesFramework\HbPFCustomNodeBundle\Controller
 *
 * @Route(service="hbpf.custom.custom_node")
 */
class CustomNodeController extends FOSRestController
{

    /**
     * @var CustomNodeHandler
     */
    private $handler;

    /**
     * CustomNodeController constructor.
     *
     * @param CustomNodeHandler $handler
     */
    function __construct(CustomNodeHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/custom_node/{nodeId}/process", defaults={}, requirements={"nodeId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $nodeId
     *
     * @return JsonResponse
     */
    public function sendAction(Request $request, string $nodeId): JsonResponse
    {
        try {
            $result   = $this->handler->process($nodeId, (string) $request->getContent(), $request->headers->all());
            $response = new JsonResponse($result->getData(), 200, $result->getHeaders(), TRUE);
        } catch (CustomNodeException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/custom_node/{nodeId}/process/test", defaults={}, requirements={"nodeId": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $nodeId
     *
     * @return JsonResponse
     */
    public function sendTestAction(string $nodeId): JsonResponse
    {
        try {
            $this->handler->processTest($nodeId);
            $response = new JsonResponse([], 200);
        } catch (CustomNodeException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

}