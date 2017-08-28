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
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CustomNodeController
 *
 * @package Hanaboso\PipesFramework\HbPFCustomNodeBundle\Controller
 */
class CustomNodeController extends FOSRestController
{

    /**
     * @var CustomNodeHandler
     */
    private $handler;

    /**
     * JoinerController constructor.
     *
     * @param CustomNodeHandler $handler
     */
    function __construct(CustomNodeHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/api/custom_node/{nodeId}/process", defaults={}, requirements={"nodeId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $nodeId
     *
     * @return Response
     */
    public function sendAction(Request $request, string $nodeId): Response
    {
        $res = $this->handler->process($nodeId, $request->request->all());

        return $this->handleView($this->view($res));
    }

    /**
     * @Route("/api/custom_node/{nodeId}/process/test", defaults={}, requirements={"nodeId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $nodeId
     *
     * @return Response
     */
    public function sendActionTest(string $nodeId): Response
    {
        $this->handler->processTest($nodeId);

        return $this->handleView($this->view());
    }

}