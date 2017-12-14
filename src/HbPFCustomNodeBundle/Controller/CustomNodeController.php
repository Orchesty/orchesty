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
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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

    use ControllerTrait;

    /**
     * @var CustomNodeHandler
     */
    private $handler;

    /**
     * CustomNodeController constructor.
     *
     * @param CustomNodeHandler $handler
     */
    public function __construct(CustomNodeHandler $handler)
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
            $data = $this->handler->process($nodeId, (string) $request->getContent(), $request->headers->all());

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders([], $e));
        }
    }

    /**
     * @Route("/custom_node/{nodeId}/process/test")
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $nodeId
     *
     * @return Response
     */
    public function sendTestAction(string $nodeId): Response
    {
        try {
            $this->handler->processTest($nodeId);

            return $this->getResponse([]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}