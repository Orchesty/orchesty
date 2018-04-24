<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/4/17
 * Time: 11:59 AM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class StartingPointController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
class StartingPointController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var StartingPointHandler
     */
    private $startingPointHandler;

    /**
     * StartingPointController constructor.
     *
     * @param StartingPointHandler $startingPointHandler
     */
    public function __construct(StartingPointHandler $startingPointHandler)
    {
        $this->startingPointHandler = $startingPointHandler;
    }

    /**
     * @Route("/topologies/{topologyName}/nodes/{nodeName}/run")
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     *
     * @return Response
     */
    public function runAction(Request $request, string $topologyName, string $nodeName): Response
    {
        try {
            $this->startingPointHandler->runWithRequest($request, $topologyName, $nodeName);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }

        return $this->getResponse([]);
    }

    /**
     * @Route("/topologies/{topologyId}/nodes/{nodeId}/run_by_id")
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string  $topologyId
     * @param string  $nodeId
     *
     * @return Response
     */
    public function runByIdAction(Request $request, string $topologyId, string $nodeId): Response
    {
        try {
            $this->startingPointHandler->runWithRequestById($request, $topologyId, $nodeId);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }

        return $this->getResponse([]);
    }

    /**
     * @Route("/topologies/{topologyId}/test")
     * @Method({"GET"})
     *
     * @param string $topologyId
     *
     * @return Response
     */
    public function testAction(string $topologyId): Response
    {
        $data = $this->startingPointHandler->runTest($topologyId);

        return $this->getResponse($data, 200, ['Content-Type' => 'application/json']);
    }

}