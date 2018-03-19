<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 1:53 PM
 */

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFLogsBundle\Handler\LogsHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JoinerController
 *
 * @package Hanaboso\PipesFramework\HbPFJoinerBundle\Controller
 */
class LogsController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var LogsHandler
     */
    private $logsHandler;

    /**
     * LogsController constructor.
     *
     * @param LogsHandler $logsHandler
     */
    public function __construct(LogsHandler $logsHandler)
    {
        $this->logsHandler = $logsHandler;
    }

    /**
     * @Route("/logs")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getDataForTableAction(Request $request): Response
    {
        $data = $this->logsHandler->getData(
            $request->query->get('limit', "10"),
            $request->query->get('offset', "0")
        );

        return new JsonResponse($data);
    }

}