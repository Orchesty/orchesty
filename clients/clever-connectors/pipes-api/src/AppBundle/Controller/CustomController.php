<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Handler\CustomHandler;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SyncController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="cc.custom.controller")
 */
final class CustomController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var CustomHandler
     */
    private $handler;

    /**
     * @param CustomHandler $handler
     */
    public function __construct(CustomHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/system/{system}/action/{action}")
     * @Method({"GET", "POST","OPTIONS"})
     *
     * @param Request $request
     * @param string  $system
     * @param string  $action
     *
     * @return Response
     */
    public function runCustomSystemActionAction(Request $request, string $system, string $action): Response
    {
        try {
            $data = $request->request->all();
            $data = $this->handler->runCustomSystemAction($system, $action, $data);

            return $this->getResponse($data, 200);
        } catch (SystemException $e) {
            return $this->getErrorResponse($e);
        }
    }

}
