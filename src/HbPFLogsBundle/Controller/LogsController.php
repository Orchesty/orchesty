<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Controller;

use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFLogsBundle\Handler\LogsHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogsController
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\Controller
 */
class LogsController
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
     * @Route("/logs", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getDataForTableAction(Request $request): Response
    {
        return new JsonResponse($this->logsHandler->getData(new GridRequestDto($request->headers->all())));
    }

}
