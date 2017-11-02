<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:53 PM
 */

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
     * @Route("/custom_node/{nodeId}/process")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $nodeId
     *
     * @return Response
     */
    public function sendAction(Request $request, string $nodeId): Response
    {
        try {
            $data     = $this->handler->process($nodeId, (string) $request->getContent(), $request->headers->all());
            $response = new Response(
                $data->getData(),
                200,
                ControllerUtils::createHeaders($data->getHeaders())
            );
        } catch (Exception|Throwable $e) {
            $response = new Response(
                ControllerUtils::createExceptionData($e),
                500,
                ControllerUtils::createHeaders([], $e)
            );
        }

        return $response;
    }

    /**
     * @Route("/custom_node/{nodeId}/process/test")
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
        } catch (Exception|Throwable $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

}